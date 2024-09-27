<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Config;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;
use RemoteDataBlocks\Config\QueryRunner\QueryRunner;
use RemoteDataBlocks\Config\QueryRunner\QueryRunnerInterface;
use RemoteDataBlocks\HttpClient\HttpClient;
use RemoteDataBlocks\Tests\Mocks\MockDataSource;
use RemoteDataBlocks\Tests\Mocks\MockValidator;
use WP_Error;

class QueryRunnerTest extends TestCase {
	private MockDataSource $http_data_source;
	private HttpQueryContext $query_context;
	private HttpClient $http_client;

	protected function setUp(): void {
		parent::setUp();

		$this->http_client     = $this->createMock( HttpClient::class );
		$this->http_data_source = MockDataSource::from_array( MockDataSource::MOCK_CONFIG, new MockValidator() );

		$this->query_context = new class($this->http_data_source, $this->http_client) extends HttpQueryContext {
			private string $request_method = 'GET';
			private array $request_body    = [ 'query' => 'test' ];
			private mixed $response_data   = null;

			public function __construct( private HttpDataSource $http_data_source, private HttpClient $http_client ) {
				parent::__construct( $http_data_source );
			}

			public function get_endpoint( array $input_variables = [] ): string {
				return $this->http_data_source->get_endpoint();
			}

			public function get_image_url(): ?string {
				return null;
			}

			public function get_request_method(): string {
				return $this->request_method;
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
		};
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
		$input_variables = [ 'key' => 'value' ];
		$response_body   = wp_json_encode( [
			'data' => [
				'id'   => 1,
				'name' => 'Test',
			],
		] );
		$response        = new Response( 200, [], $response_body );

		$this->http_data_source->set_endpoint( $endpoint );
		$this->http_client->method( 'request' )->willReturn( $response );

		$query_runner = $this->query_context->get_query_runner();
		$result       = $query_runner->execute( $input_variables );

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
		$input_variables = [ 'key' => 'value' ];

		$this->http_data_source->set_endpoint( $endpoint );

		$query_runner = $this->query_context->get_query_runner();
		$result       = $query_runner->execute( $input_variables );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( $expected_error_code, $result->get_error_code() );
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

		$this->query_context->output_schema = [
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

		$this->assertArrayHasKey( 'metadata', $result );
		$this->assertArrayHasKey( 'total_count', $result['metadata'] );
		$this->assertSame( 1, $result['metadata']['total_count']['value'] );

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
		$this->query_context->output_schema = [
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

		$this->assertArrayHasKey( 'metadata', $result );
		$this->assertArrayHasKey( 'total_count', $result['metadata'] );
		$this->assertSame( 1, $result['metadata']['total_count']['value'] );

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
		$this->query_context->output_schema = [
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

		$this->assertArrayHasKey( 'metadata', $result );
		$this->assertArrayHasKey( 'total_count', $result['metadata'] );
		$this->assertSame( 1, $result['metadata']['total_count']['value'] );

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
		$this->query_context->output_schema = [
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

		$this->assertArrayHasKey( 'metadata', $result );
		$this->assertArrayHasKey( 'total_count', $result['metadata'] );
		$this->assertSame( 1, $result['metadata']['total_count']['value'] );

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
