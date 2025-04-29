<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Config;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Config\QueryRunner\QueryRunner;
use RemoteDataBlocks\HttpClient\HttpClient;
use RemoteDataBlocks\Tests\Mocks\MockDataSource;
use RemoteDataBlocks\Tests\Mocks\MockQuery;
use WP_Error;

class QueryRunnerTest extends TestCase {
	private MockDataSource $http_data_source;
	private MockQuery $query;
	private HttpClient $http_client;

	protected function setUp(): void {
		parent::setUp();

		$this->http_client = $this->createMock( HttpClient::class );
		$this->http_data_source = MockDataSource::create();

		$this->query = MockQuery::create( [
			'data_source' => $this->http_data_source,
			'query_runner' => new QueryRunner( $this->http_client, [] ),
		] );
	}

	public static function provideValidEndpoints(): array {
		return [
			[
				'https://example.com/api',
			],
			[
				'https://example.com/api?foo=bar',
			],
			[
				'https://user@example.com/api?foo=bar',
			],
			[
				'https://user:pass@example.com/api?foo=bar',
			],
			[
				'https://:pass@example.com/api?foo=bar',
			],
			[
				'https://example.com:80/api?foo=bar',
			],
			[
				'https://user:pass@example.com:80/api?foo=bar',
			],
			[
				'https://🤡@🚗/🎉',
			],
		];
	}

	/**
		* @dataProvider provideValidEndpoints
	 */
	public function testExecuteSuccessfulRequest( string $endpoint ): void {
		$response_body = wp_json_encode( [
			'data' => [
				'id' => 1,
				'name' => 'Test',
			],
		] );
		$response = new Response( 200, [], $response_body );

		$this->query->set_output_schema( [
			'is_collection' => false,
			'path' => '$.data',
			'type' => [
				'id' => [
					'name' => 'ID',
					'type' => 'id',
				],
				'name' => [
					'name' => 'Name',
					'type' => 'string',
				],
			],
		] );

		$this->http_data_source->set_endpoint( $endpoint );
		$this->http_client->method( 'request' )->willReturn( $response );

		$result = $this->query->execute( [] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'results', $result );
	}

	public static function provideInvalidEndpoints(): array {
		return [
			[
				'https://:80/hello',
				'Unable to parse endpoint URL',
			],
			[
				'https:///hello',
				'Unable to parse endpoint URL',
			],
			[
				'https://example.com:PORT/hello',
				'Unable to parse endpoint URL',
			],
			[
				'http://api.example.com',
				'Invalid endpoint URL scheme',
			],
			[
				'ftp://api.example.com',
				'Invalid endpoint URL scheme',
			],
			[
				'//api.example.com',
				'Invalid endpoint URL scheme',
			],
			[
				'://api.example.com',
				'Invalid endpoint URL scheme',
			],
			[
				'🤡://example.com/hello',
				'Invalid endpoint URL scheme',
			],
			[
				'https:/hello',
				'Invalid endpoint URL host',
			],
		];
	}

	/**
		* @dataProvider provideInvalidEndpoints
	 */
	public function testExecuteInvalidEndpoints( string $endpoint, string $expected_error_code ): void {
		$this->http_data_source->set_endpoint( $endpoint );

		$result = $this->query->execute( [] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( $expected_error_code, $result->get_error_code() );
	}

	public function testExecuteHttpClientException(): void {
		$this->http_client->method( 'request' )->willThrowException( new \Exception( 'HTTP Client Error' ) );

		$query_runner = new QueryRunner( $this->http_client );
		$result = $query_runner->execute( $this->query, [] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'remote-data-blocks-unexpected-exception', $result->get_error_code() );
	}

	public function testExecuteBadStatusCode(): void {
		$response = new \GuzzleHttp\Psr7\Response( 400, [], 'Bad Request' );
		$this->http_client->method( 'request' )->willReturn( $response );

		$query_runner = new QueryRunner( $this->http_client );
		$result = $query_runner->execute( $this->query, [] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'remote-data-blocks-bad-status-code', $result->get_error_code() );
	}

	public function testExecuteSuccessfulResponse(): void {
		$response_body = $this->createMock( \Psr\Http\Message\StreamInterface::class );
		$response_body->method( 'getContents' )->willReturn( wp_json_encode( [ 'test' => 'test value' ] ) );

		$response = new Response( 200, [], $response_body );

		$this->http_client->method( 'request' )->willReturn( $response );

		$this->query->set_output_schema( [
			'is_collection' => false,
			'type' => [
				'test' => [
					'name' => 'Test Field',
					'path' => '$.test',
					'type' => 'string',
				],
			],
		] );

		$result = $this->query->execute( [] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'results', $result );

		$this->assertArrayHasKey( 'metadata', $result );
		$this->assertArrayHasKey( 'total_count', $result['metadata'] );
		$this->assertSame( 1, $result['metadata']['total_count']['value'] );

		$expected_result = [
			'result' => [
				'test' => [
					'name' => 'Test Field',
					'type' => 'string',
					'value' => 'test value',
				],
			],
			'uuid' => '00000000-0000-4000-8000-000000000000',
		];

		$this->assertIsArray( $result['results'] );
		$this->assertCount( 1, $result['results'] );
		$this->assertSame( $expected_result, $result['results'][0] );
	}

	public function testExecuteSuccessfulResponseWithCollectionResponse(): void {
		$response_body = $this->createMock( \Psr\Http\Message\StreamInterface::class );
		$response_body->method( 'getContents' )->willReturn(
			wp_json_encode( [
				'values' => [
					[
						'test' => 'test value 1',
					],
					[
						'test' => 'test value 2',
					],
				],
			] )
		);

		$response = new Response( 200, [], $response_body );

		$this->http_client->method( 'request' )->willReturn( $response );

		$this->query->set_output_schema( [
			'is_collection' => true,
			'path' => '$.values[*]',
			'type' => [
				'test' => [
					'name' => 'Test Field',
					'path' => '$.test',
					'type' => 'string',
				],
			],
		] );

		$result = $this->query->execute( [] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'results', $result );

		$this->assertArrayHasKey( 'metadata', $result );
		$this->assertArrayHasKey( 'total_count', $result['metadata'] );
		$this->assertSame( 2, $result['metadata']['total_count']['value'] );

		$expected_results = [
			[
				'result' => [
					'test' => [
						'name' => 'Test Field',
						'type' => 'string',
						'value' => 'test value 1',
					],
				],
				'uuid' => '00000000-0000-4000-8000-000000000000',
			],
			[
				'result' => [
					'test' => [
						'name' => 'Test Field',
						'type' => 'string',
						'value' => 'test value 2',
					],
				],
				'uuid' => '00000000-0000-4000-8000-000000000000',
			],
		];

		$this->assertIsArray( $result['results'] );
		$this->assertCount( 2, $result['results'] );
		$this->assertSame( $expected_results, $result['results'] );
	}

	public function testExecuteSuccessfulResponseWithCollectionResponseAtRoot(): void {
		$response_body = $this->createMock( \Psr\Http\Message\StreamInterface::class );
		$response_body->method( 'getContents' )->willReturn(
			wp_json_encode( [
				[
					'test' => 'test value 1',
				],
				[
					'test' => 'test value 2',
				],
			] )
		);

		$response = new Response( 200, [], $response_body );

		$this->http_client->method( 'request' )->willReturn( $response );

		$this->query->set_output_schema( [
			'is_collection' => true,
			'type' => [
				'test' => [
					'name' => 'Test Field',
					'path' => '$.test',
					'type' => 'string',
				],
			],
		] );

		$result = $this->query->execute( [] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'results', $result );

		$this->assertArrayHasKey( 'metadata', $result );
		$this->assertArrayHasKey( 'total_count', $result['metadata'] );
		$this->assertSame( 2, $result['metadata']['total_count']['value'] );

		$expected_results = [
			[
				'result' => [
					'test' => [
						'name' => 'Test Field',
						'type' => 'string',
						'value' => 'test value 1',
					],
				],
				'uuid' => '00000000-0000-4000-8000-000000000000',
			],
			[
				'result' => [
					'test' => [
						'name' => 'Test Field',
						'type' => 'string',
						'value' => 'test value 2',
					],
				],
				'uuid' => '00000000-0000-4000-8000-000000000000',
			],
		];

		$this->assertIsArray( $result['results'] );
		$this->assertCount( 2, $result['results'] );
		$this->assertSame( $expected_results, $result['results'] );
	}

	public function testExecuteSuccessfulResponseWithJsonStringResponseData(): void {
		$response_body = $this->createMock( \Psr\Http\Message\StreamInterface::class );
		$response = new Response( 200, [], $response_body );

		$this->http_client->method( 'request' )->willReturn( $response );

		$this->query->set_response_data( '{"test":"overridden in preprocess_response as JSON string"}' );
		$this->query->set_output_schema( [
			'is_collection' => false,
			'type' => [
				'test' => [
					'name' => 'Test Field',
					'path' => '$.test',
					'type' => 'string',
				],
			],
		] );

		$result = $this->query->execute( [] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'results', $result );

		$this->assertArrayHasKey( 'metadata', $result );
		$this->assertArrayHasKey( 'total_count', $result['metadata'] );
		$this->assertSame( 1, $result['metadata']['total_count']['value'] );

		$expected_result = [
			'result' => [
				'test' => [
					'name' => 'Test Field',
					'type' => 'string',
					'value' => 'overridden in preprocess_response as JSON string',
				],
			],
			'uuid' => '00000000-0000-4000-8000-000000000000',
		];

		$this->assertIsArray( $result['results'] );
		$this->assertCount( 1, $result['results'] );
		$this->assertSame( $expected_result, $result['results'][0] );
	}

	public function testExecuteSuccessfulResponseWithArrayResponseData(): void {
		$response_body = $this->createMock( \Psr\Http\Message\StreamInterface::class );

		$response = new Response( 200, [], $response_body );

		$this->http_client->method( 'request' )->willReturn( $response );

		$this->query->set_response_data( [ 'test' => 'overridden in preprocess_response as array' ] );
		$this->query->set_output_schema( [
			'is_collection' => false,
			'type' => [
				'test' => [
					'name' => 'Test Field',
					'path' => '$.test',
					'type' => 'string',
				],
			],
		] );

		$result = $this->query->execute( [] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'results', $result );

		$this->assertArrayHasKey( 'metadata', $result );
		$this->assertArrayHasKey( 'total_count', $result['metadata'] );
		$this->assertSame( 1, $result['metadata']['total_count']['value'] );

		$expected_result = [
			'result' => [
				'test' => [
					'name' => 'Test Field',
					'type' => 'string',
					'value' => 'overridden in preprocess_response as array',
				],
			],
			'uuid' => '00000000-0000-4000-8000-000000000000',
		];

		$this->assertIsArray( $result['results'] );
		$this->assertCount( 1, $result['results'] );
		$this->assertSame( $expected_result, $result['results'][0] );
	}

	public function testExecuteSuccessfulResponseWithObjectResponseData(): void {
		$response_body = $this->createMock( \Psr\Http\Message\StreamInterface::class );
		$response = new Response( 200, [], $response_body );

		$this->http_client->method( 'request' )->willReturn( $response );

		$response_data = new \stdClass();
		$response_data->test = 'overridden in preprocess_response as object';

		$this->query->set_response_data( $response_data );
		$this->query->set_output_schema( [
			'is_collection' => false,
			'type' => [
				'test' => [
					'name' => 'Test Field',
					'path' => '$.test',
					'type' => 'string',
				],
			],
		] );

		$result = $this->query->execute( [] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'results', $result );

		$this->assertArrayHasKey( 'metadata', $result );
		$this->assertArrayHasKey( 'total_count', $result['metadata'] );
		$this->assertSame( 1, $result['metadata']['total_count']['value'] );

		$expected_result = [
			'result' => [
				'test' => [
					'name' => 'Test Field',
					'type' => 'string',
					'value' => 'overridden in preprocess_response as object',
				],
			],
			'uuid' => '00000000-0000-4000-8000-000000000000',
		];

		$this->assertIsArray( $result['results'] );
		$this->assertCount( 1, $result['results'] );
		$this->assertSame( $expected_result, $result['results'][0] );
	}

	public function testQueryRunnerAppliesDefaultInputVariables(): void {
		$query = MockQuery::create( [
			'data_source' => $this->http_data_source,
			'endpoint' => function ( array $input_variables ): string {
				return sprintf(
					'https://example.com/api?foo=%s&baz=%s',
					$input_variables['foo'] ?? 'MISSING',
					$input_variables['baz'] ?? 'MISSING',
				);
			},
			'input_schema' => [
				'baz' => [
					'name' => 'Baz',
					'type' => 'string',
				],
				'foo' => [
					'default_value' => 'bar',
					'name' => 'Foo',
					'type' => 'string',
				],
			],
			'query_runner' => new QueryRunner( $this->http_client ),
		] );

		$response_body = $this->createMock( \Psr\Http\Message\StreamInterface::class );
		$response = new Response( 200, [], $response_body );

		$this
			->http_client
			->expects( $this->exactly( 1 ) )
			->method( 'request' )
			->willReturn( $response )
			->with( 'GET', 'https://example.com/api?foo=bar&baz=MISSING' );

		$result = $query->execute( [] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'metadata', $result );
		$this->assertArrayHasKey( 'results', $result );
	}

	public function testSubsequentRequestsResolveFromInMemoryCache(): void {
		$response_body = wp_json_encode( [
			'data' => [
				'id' => 1,
				'name' => 'Test',
			],
		] );
		$response = new Response( 200, [], $response_body );

		$this->query->set_output_schema( [
			'is_collection' => false,
			'path' => '$.data',
			'type' => [
				'id' => [
					'name' => 'ID',
					'type' => 'id',
				],
				'name' => [
					'name' => 'Name',
					'type' => 'string',
				],
			],
		] );

		$this->http_client->method( 'request' )->willReturn( $response );

		$result = $this->query->execute( [] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'results', $result );
		$this->assertEquals( 1, $result['results'][0]['result']['id']['value'] );
		$this->assertEquals( 'Test', $result['results'][0]['result']['name']['value'] );

		$updated_response_body = wp_json_encode( [
			'data' => [
				'id' => 2,
				'name' => 'Test 2',
			],
		] );
		$updated_response = new Response( 200, [], $updated_response_body );

		$this->http_client->method( 'request' )->willReturn( $updated_response );

		$result = $this->query->execute( [] );

		// Returns original response from in-memory cache.
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'results', $result );
		$this->assertEquals( 1, $result['results'][0]['result']['id']['value'] );
		$this->assertEquals( 'Test', $result['results'][0]['result']['name']['value'] );
	}
}
