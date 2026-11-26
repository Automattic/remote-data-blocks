<?php declare(strict_types = 1);

namespace RemoteDataBlocks\IntegrationTests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Kevinrob\GuzzleCache\Storage\VolatileRuntimeStorage;
use RDBTestCase;
use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Config\Query\HttpQuery;
use RemoteDataBlocks\Config\QueryRunner\QueryRunner;
use RemoteDataBlocks\HttpClient\HttpClient;
use RemoteDataBlocks\HttpClient\RdbCacheMiddleware;
use RemoteDataBlocks\HttpClient\RdbCacheStrategy;
use WP_Error;

final class ParsedQueryCacheTest extends RDBTestCase {
	/** @dataProvider schemaOrders */
	public function testQueriesShareHttpResponseButParseTheirOwnSchema( string $first_path, string $second_path ): void {
		$transport = new MockHandler( [
			new Response( 200, [], wp_json_encode( [
				'first' => 'First value',
				'second' => 'Second value',
			] ) ),
		] );
		$stack = HandlerStack::create( $transport );
		$stack->push( new RdbCacheMiddleware( new RdbCacheStrategy( new VolatileRuntimeStorage() ) ) );
		$client = new Client( [ 'handler' => $stack ] );
		$http_client = $this->createMock( HttpClient::class );
		$http_client->expects( $this->exactly( 2 ) )->method( 'request' )->willReturnCallback(
			static fn ( $method, $uri, $options ) => HttpClient::instance()->request( $method, $uri, $options, $client )
		);
		$runner = new QueryRunner( $http_client, [] );
		$first = $this->create_query( $runner, [
			'type' => 'string',
			'path' => '$.' . $first_path,
		] );
		$second = $this->create_query( $runner, [
			'type' => 'string',
			'path' => '$.' . $second_path,
		] );
		$inputs = [ 'per_page' => 10 ];

		$first_response = $first->execute( $inputs );
		$second_response = $second->execute( $inputs );
		$this->assertIsArray( $first_response );
		$this->assertIsArray( $second_response );
		$this->assertSame( ucfirst( $first_path ) . ' value', $first_response['results'][0]['result']['value']['value'] );
		$this->assertSame( ucfirst( $second_path ) . ' value', $second_response['results'][0]['result']['value']['value'] );
		$this->assertSame( $first_response, $first->execute( $inputs ) );
		$this->assertSame( $second_response, $second->execute( $inputs ) );
		$this->assertCount( 0, $transport );
	}

	public static function schemaOrders(): array {
		return [
			'first schema first' => [ 'first', 'second' ],
			'second schema first' => [ 'second', 'first' ],
		];
	}

	public function testSameQuerySeparatesInputsAndRequestDetails(): void {
		$runner = new class(null, []) extends QueryRunner {
			protected function get_raw_response_data( array $request_details, array $input_variables ): array {
				return [
					'input_variables' => $input_variables,
					'metadata' => [],
					'response_data' => [ 'variant' => $request_details['fixture_variant'] ?? 'Original' ],
				];
			}
		};
		$query = $this->create_query( $runner, [
			'type' => 'string',
			'generate' => static fn ( $data, $raw ) => $data['variant'] . ' ' . $raw['input_variables']['per_page'],
		] );
		$inputs = [ 'per_page' => 10 ];
		$first = $query->execute( $inputs );
		$this->assertIsArray( $first );
		$this->assertSame( 'Original 10', $first['results'][0]['result']['value']['value'] );

		$other_input = $query->execute( [ 'per_page' => 20 ] );
		$this->assertIsArray( $other_input );
		$this->assertSame( 'Original 20', $other_input['results'][0]['result']['value']['value'] );
		$this->assertSame( [ [ 'per_page' => 20 ] ], $other_input['query_inputs'] );

		$alter_request = static function ( array $details ): array {
			$details['fixture_variant'] = 'Changed';
			return $details;
		};
		add_filter( 'remote_data_blocks_request_details', $alter_request );
		try {
			$other_request = $query->execute( $inputs );
			$this->assertIsArray( $other_request );
			$this->assertSame( 'Changed 10', $other_request['results'][0]['result']['value']['value'] );
			$this->assertSame( $other_request, $query->execute( $inputs ) );
		} finally {
			remove_filter( 'remote_data_blocks_request_details', $alter_request );
		}

		$this->assertSame( $first, $query->execute( $inputs ) );
	}

	public function testExpiredQueriesReleaseParsedResultsAndCacheHooksStillRun(): void {
		$runner = new class(null, []) extends QueryRunner {
			public int $cache_hits = 0;
			public int $cache_saves = 0;

			public function count_cached_results(): int {
				return count( self::$in_memory_cache );
			}

			protected function cache_get( string $cache_key ): array|WP_Error|null {
				$result = parent::cache_get( $cache_key );
				if ( null !== $result ) {
					++$this->cache_hits;
				}
				return $result;
			}

			protected function cache_save( string $cache_key, array|WP_Error $data ): array|WP_Error {
				++$this->cache_saves;
				return parent::cache_save( $cache_key, $data );
			}

			protected function get_raw_response_data( array $request_details, array $input_variables ): array {
				return [
					'input_variables' => $input_variables,
					'metadata' => [],
					'response_data' => [ 'value' => 'Fixture value' ],
				];
			}
		};
		$live_query = $this->create_query( $runner, [ 'type' => 'string' ] );
		$inputs = [ 'per_page' => 10 ];
		$live_response = $live_query->execute( $inputs );
		$this->assertIsArray( $live_response );
		$live_count = $runner->count_cached_results();
		$this->assertGreaterThan( 0, $live_count );

		for ( $index = 0; $index < 4; ++$index ) {
			$query = clone $live_query;
			$this->assertIsArray( $query->execute( $inputs ) );
			$this->assertSame( $live_count + 1, $runner->count_cached_results() );
			unset( $query );
			$this->assertSame( $live_count, $runner->count_cached_results() );
		}

		$this->assertSame( $live_response, $live_query->execute( $inputs ) );
		$this->assertSame( 1, $runner->cache_hits );
		$this->assertSame( 5, $runner->cache_saves );
		unset( $live_query );
		$this->assertSame( $live_count - 1, $runner->count_cached_results() );
	}

	private function create_query( QueryRunner $runner, array $mapping ): HttpQuery {
		$query = HttpQuery::from_array( [
			'data_source' => HttpDataSource::from_array( [
				'__version' => 1,
				'display_name' => 'Parsed query cache fixture',
				'endpoint' => 'https://example.com/query-cache',
			] ),
			'query_runner' => $runner,
			'input_schema' => [ 'per_page' => [ 'type' => 'ui:pagination_per_page' ] ],
			'output_schema' => [ 'type' => [ 'value' => $mapping ] ],
		] );
		$this->assertInstanceOf( HttpQuery::class, $query );
		return $query;
	}
}
