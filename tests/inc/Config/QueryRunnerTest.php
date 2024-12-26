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
		$this->http_data_source = MockDataSource::from_array();

		$this->query = MockQuery::from_array( [
			'data_source' => $this->http_data_source,
			'query_runner' => new QueryRunner( $this->http_client ),
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
	public function testExecuteSuccessfulRequest( string $endpoint ) {
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
		$this->assertArrayHasKey( 'is_collection', $result );
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
	public function testExecuteInvalidEndpoints( string $endpoint, string $expected_error_code ) {
		$this->http_data_source->set_endpoint( $endpoint );

		$result = $this->query->execute( [] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( $expected_error_code, $result->get_error_code() );
	}

	public function testExecuteHttpClientException() {
		$this->http_client->method( 'request' )->willThrowException( new \Exception( 'HTTP Client Error' ) );

		$query_runner = new QueryRunner( $this->http_client );
		$result = $query_runner->execute( $this->query, [] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'remote-data-blocks-unexpected-exception', $result->get_error_code() );
	}

	public function testExecuteBadStatusCode() {
		$response = new \GuzzleHttp\Psr7\Response( 400, [], 'Bad Request' );
		$this->http_client->method( 'request' )->willReturn( $response );

		$query_runner = new QueryRunner( $this->http_client );
		$result = $query_runner->execute( $this->query, [] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'remote-data-blocks-bad-status-code', $result->get_error_code() );
	}

	public function testExecuteSuccessfulResponse() {
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
		$this->assertArrayHasKey( 'is_collection', $result );
		$this->assertArrayHasKey( 'results', $result );
		$this->assertFalse( $result['is_collection'] );

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
		];

		$this->assertIsArray( $result['results'] );
		$this->assertCount( 1, $result['results'] );
		$this->assertSame( $expected_result, $result['results'][0] );
	}

	public function testExecuteSuccessfulResponseWithJsonStringResponseData() {
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
		$this->assertArrayHasKey( 'is_collection', $result );
		$this->assertArrayHasKey( 'results', $result );
		$this->assertFalse( $result['is_collection'] );

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
		];

		$this->assertIsArray( $result['results'] );
		$this->assertCount( 1, $result['results'] );
		$this->assertSame( $expected_result, $result['results'][0] );
	}

	public function testExecuteSuccessfulResponseWithArrayResponseData() {
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
		$this->assertArrayHasKey( 'is_collection', $result );
		$this->assertArrayHasKey( 'results', $result );
		$this->assertFalse( $result['is_collection'] );

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
		];

		$this->assertIsArray( $result['results'] );
		$this->assertCount( 1, $result['results'] );
		$this->assertSame( $expected_result, $result['results'][0] );
	}

	public function testExecuteSuccessfulResponseWithObjectResponseData() {
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
		$this->assertArrayHasKey( 'is_collection', $result );
		$this->assertArrayHasKey( 'results', $result );
		$this->assertFalse( $result['is_collection'] );

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
		];

		$this->assertIsArray( $result['results'] );
		$this->assertCount( 1, $result['results'] );
		$this->assertSame( $expected_result, $result['results'][0] );
	}
}
