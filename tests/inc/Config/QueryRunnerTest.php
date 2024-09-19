<?php

namespace RemoteDataBlocks\Tests\Config;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Config\Datasource\HttpDatasource;
use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;
use RemoteDataBlocks\Config\QueryRunner\QueryRunner;
use RemoteDataBlocks\Config\QueryRunner\QueryRunnerInterface;
use RemoteDataBlocks\HttpClient\HttpClient;
use RemoteDataBlocks\Tests\Mocks\MockDatasource;
use WP_Error;

class QueryRunnerTest extends TestCase {

	private $http_datasource;
	private $query_context;
	private $http_client;

	protected function setUp(): void {
		parent::setUp();

		$this->http_client = $this->createMock( HttpClient::class );

		$this->http_datasource = new class() extends MockDatasource {
			private $endpoint = 'https://example.com/api';
			private $headers  = [ 'Content-Type' => 'application/json' ];

			public function get_endpoint(): string {
				return $this->endpoint;
			}

			public function get_request_headers(): array {
				return $this->headers;
			}

			public function set_endpoint( string $endpoint ): void {
				$this->endpoint = $endpoint;
			}

			public function set_headers( array $headers ): void {
				$this->headers = $headers;
			}
		};

		$this->query_context = new class($this->http_datasource, $this->http_client) extends HttpQueryContext {
			private $http_datasource;
			private $http_client;
			private $request_method = 'GET';
			private $request_body   = [ 'query' => 'test' ];
			private $response_data  = null;

			public function __construct( HttpDatasource $http_datasource, HttpClient $http_client ) {
				$this->http_datasource = $http_datasource;
				$this->http_client     = $http_client;
			}

			public function get_endpoint( array $input_variables = [] ): string {
				return $this->http_datasource->get_endpoint();
			}

			public function get_image_url(): ?string {
				return null;
			}

			public function get_metadata( array $response_metadata, array $query_results ): array {
				return [];
			}

			public function get_request_method(): string {
				return $this->request_method;
			}

			public function get_request_headers( array $input_variables = [] ): array {
				return $this->http_datasource->get_request_headers();
			}

			public function get_request_body( array $input_variables ): array|null {
				return $this->request_body;
			}

			public function get_query_name(): string {
				return 'Query';
			}

			public function get_query_runner(): QueryRunnerInterface {
				return new QueryRunner( $this, $this->http_client );
			}

			public function process_response( string $raw_response_data, array $input_variables ): string|array|object|null {
				if ( null !== $this->response_data ) {
					return $this->response_data;
				}

				return $raw_response_data;
			}

			public function set_request_method( string $method ): void {
				$this->request_method = $method;
			}

			public function set_request_body( array $body ): void {
				$this->request_body = $body;
			}

			public function set_response_data( string|array|object|null $data ): void {
				$this->response_data = $data;
			}

			public array $output_variables = [];
		};
	}

	public function testExecuteSuccessfulRequest() {
		$input_variables = [ 'key' => 'value' ];
		$response_body   = wp_json_encode( [
			'data' => [
				'id'   => 1,
				'name' => 'Test',
			],
		] );
		$response        = new Response( 200, [], $response_body );

		$this->http_client->method( 'request' )->willReturn( $response );

		$query_runner = $this->query_context->get_query_runner();
		$result       = $query_runner->execute( $input_variables );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'is_collection', $result );
		$this->assertArrayHasKey( 'results', $result );
	}

	public function testExecuteInvalidScheme() {
		$input_variables = [ 'key' => 'value' ];

		$this->http_datasource->set_endpoint( 'http://api.example.com' );

		$query_runner = $this->query_context->get_query_runner();
		$result       = $query_runner->execute( $input_variables );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Invalid endpoint URL scheme', $result->get_error_code() );
	}

	public function testExecuteInvalidHost() {
		$input_variables = [ 'key' => 'value' ];

		$this->http_datasource->set_endpoint( 'https://' );

		$query_runner = $this->query_context->get_query_runner();
		$result       = $query_runner->execute( $input_variables );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'Invalid endpoint URL parse', $result->get_error_code() );
	}

	public function testExecuteHttpClientException() {
		$input_variables = [ 'key' => 'value' ];

		$this->http_client->method( 'request' )->willThrowException( new \Exception( 'HTTP Client Error' ) );

		$query_runner = new QueryRunner( $this->query_context, $this->http_client );
		$result       = $query_runner->execute( $input_variables );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'remote-data-blocks-unexpected-exception', $result->get_error_code() );
	}

	public function testExecuteBadStatusCode() {
		$input_variables = [ 'key' => 'value' ];

		$response = new \GuzzleHttp\Psr7\Response( 400, [], 'Bad Request' );
		$this->http_client->method( 'request' )->willReturn( $response );

		$query_runner = new QueryRunner( $this->query_context, $this->http_client );
		$result       = $query_runner->execute( $input_variables );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'remote-data-blocks-bad-status-code', $result->get_error_code() );
	}

	public function testExecuteSuccessfulResponse() {
		$input_variables = [ 'key' => 'value' ];

		$response_body = $this->createMock( \Psr\Http\Message\StreamInterface::class );
		$response_body->method( 'getContents' )->willReturn( wp_json_encode( [ 'test' => 'test value' ] ) );

		$response = new Response( 200, [], $response_body );

		$this->http_client->method( 'request' )->willReturn( $response );

		$this->query_context->output_variables = [
			'is_collection' => false,
			'mappings'      => [
				'test' => [
					'name' => 'Test Field',
					'path' => '$.test',
					'type' => 'string',
				],
			],
		];

		$query_runner = $this->query_context->get_query_runner();
		$result       = $query_runner->execute( $input_variables );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'is_collection', $result );
		$this->assertArrayHasKey( 'results', $result );
		$this->assertFalse( $result['is_collection'] );

		$expected_result = [
			'result' => [
				'test' => [
					'name'  => 'Test Field',
					'path'  => '$.test',
					'type'  => 'string',
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
		$response      = new Response( 200, [], $response_body );

		$this->http_client->method( 'request' )->willReturn( $response );

		$this->query_context->set_response_data( '{"test":"overridden in process_response as JSON string"}' );
		$this->query_context->output_variables = [
			'is_collection' => false,
			'mappings'      => [
				'test' => [
					'name' => 'Test Field',
					'path' => '$.test',
					'type' => 'string',
				],
			],
		];

		$query_runner = $this->query_context->get_query_runner();
		$result       = $query_runner->execute( [] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'is_collection', $result );
		$this->assertArrayHasKey( 'results', $result );
		$this->assertFalse( $result['is_collection'] );

		$expected_result = [
			'result' => [
				'test' => [
					'name'  => 'Test Field',
					'path'  => '$.test',
					'type'  => 'string',
					'value' => 'overridden in process_response as JSON string',
				],
			],
		];

		$this->assertIsArray( $result['results'] );
		$this->assertCount( 1, $result['results'] );
		$this->assertSame( $expected_result, $result['results'][0] );
	}

	public function testExecuteSuccessfulResponseWithArrayResponseData() {
		$response_body = $this->createMock( \Psr\Http\Message\StreamInterface::class );
		$response      = new Response( 200, [], $response_body );

		$this->http_client->method( 'request' )->willReturn( $response );

		$this->query_context->set_response_data( [ 'test' => 'overridden in process_response as array' ] );
		$this->query_context->output_variables = [
			'is_collection' => false,
			'mappings'      => [
				'test' => [
					'name' => 'Test Field',
					'path' => '$.test',
					'type' => 'string',
				],
			],
		];

		$query_runner = $this->query_context->get_query_runner();
		$result       = $query_runner->execute( [] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'is_collection', $result );
		$this->assertArrayHasKey( 'results', $result );
		$this->assertFalse( $result['is_collection'] );

		$expected_result = [
			'result' => [
				'test' => [
					'name'  => 'Test Field',
					'path'  => '$.test',
					'type'  => 'string',
					'value' => 'overridden in process_response as array',
				],
			],
		];

		$this->assertIsArray( $result['results'] );
		$this->assertCount( 1, $result['results'] );
		$this->assertSame( $expected_result, $result['results'][0] );
	}

	public function testExecuteSuccessfulResponseWithObjectResponseData() {
		$response_body = $this->createMock( \Psr\Http\Message\StreamInterface::class );
		$response      = new Response( 200, [], $response_body );

		$response = new Response( 200, [], $response_body );

		$this->http_client->method( 'request' )->willReturn( $response );

		$response_data       = new \stdClass();
		$response_data->test = 'overridden in process_response as object';

		$this->query_context->set_response_data( $response_data );
		$this->query_context->output_variables = [
			'is_collection' => false,
			'mappings'      => [
				'test' => [
					'name' => 'Test Field',
					'path' => '$.test',
					'type' => 'string',
				],
			],
		];

		$query_runner = $this->query_context->get_query_runner();
		$result       = $query_runner->execute( [] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'is_collection', $result );
		$this->assertArrayHasKey( 'results', $result );
		$this->assertFalse( $result['is_collection'] );

		$expected_result = [
			'result' => [
				'test' => [
					'name'  => 'Test Field',
					'path'  => '$.test',
					'type'  => 'string',
					'value' => 'overridden in process_response as object',
				],
			],
		];

		$this->assertIsArray( $result['results'] );
		$this->assertCount( 1, $result['results'] );
		$this->assertSame( $expected_result, $result['results'][0] );
	}
}
