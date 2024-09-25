<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\HttpClient;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use Kevinrob\GuzzleCache\Storage\VolatileRuntimeStorage;
use RemoteDataBlocks\HttpClient\RdbCacheMiddleware;
use RemoteDataBlocks\HttpClient\HttpClient;

class HttpClientTest extends TestCase {
	private $http_client;
	private $mock_handler;

	protected function setUp(): void {
		parent::setUp();
		$this->mock_handler = new MockHandler();
		$handler            = HandlerStack::create( $this->mock_handler );

		$handler->push( HttpClient::get_cache_middleware( new VolatileRuntimeStorage() ), 'phpunit_remote_data_blocks_cache' );
		$client = new Client( [ 'handler' => $handler ] );

		$this->http_client         = new HttpClient( 'https://api.example.com' );
		$this->http_client->client = $client;
	}

	public function testConstructor() {
		$client = new HttpClient( 'https://api.example.com', [ 'X-Test' => 'value' ] );
		$this->assertInstanceOf( HttpClient::class, $client );
	}

	public function testRequest() {
		$this->mock_handler->append( new Response( 200, [], 'Success' ) );
		$response = $this->http_client->request( 'GET', '/test' );
		$this->assertSame( 200, $response->getStatusCode() );
		$this->assertSame( 'Success', (string) $response->getBody() );
		$this->assertSame( 'MISS', $response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );
	}

	public function testGet() {
		$this->mock_handler->append( new Response( 200, [], 'GET Success' ) );
		$response = $this->http_client->get( '/test' );
		$this->assertSame( 200, $response->getStatusCode() );
		$this->assertSame( 'GET Success', (string) $response->getBody() );
		$this->assertSame( 'MISS', $response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );
	}

	public function testPost() {
		$this->mock_handler->append( new Response( 201, [], 'POST Success' ) );
		$response = $this->http_client->post( '/test' );
		$this->assertSame( 201, $response->getStatusCode() );
		$this->assertSame( 'POST Success', (string) $response->getBody() );
		$this->assertSame( 'MISS', $response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );
	}

	public function testRetryDecider() {
		$request = new Request( 'GET', '/test' );

		// Test max retries
		$this->assertFalse( HttpClient::retry_decider( 3, $request ) );

		// Test 500 status code
		$response = new Response( 500 );
		$this->assertTrue( HttpClient::retry_decider( 0, $request, $response ) );

		// Test ConnectException
		$exception = new ConnectException( 'Error Connecting', $request );
		$this->assertTrue( HttpClient::retry_decider( 0, $request, null, $exception ) );

		// Test no retry on good response
		$response = new Response( 200 );
		$this->assertFalse( HttpClient::retry_decider( 0, $request, $response ) );
	}

	public function testRetryDelay() {
		$response = new Response( 429, [ 'Retry-After' => '120' ] );
		$delay    = HttpClient::retry_delay( 1, $response );
		$this->assertSame( 120000, $delay );

		$response = new Response( 429, [ 'Retry-After' => ( new \DateTime( '+2 minutes' ) )->format( \DateTime::RFC7231 ) ] );
		$delay    = HttpClient::retry_delay( 1, $response );
		$this->assertGreaterThan( 119000, $delay );
		$this->assertLessThan( 121000, $delay );

		$response = new Response( 500 );
		$delay    = HttpClient::retry_delay( 2, $response );
		$this->assertSame( 2000, $delay );
	}

	public function testQueueRequestAndExecuteParallel() {
		$this->mock_handler->append( new Response( 200, [], 'Response 1' ) );
		$this->mock_handler->append( new Response( 201, [], 'Response 2' ) );

		$this->http_client->queue_request( 'GET', '/test1' );
		$this->http_client->queue_request( 'POST', '/test2' );

		$results = $this->http_client->execute_parallel();

		$this->assertCount( 2, $results );
		$this->assertSame( 'fulfilled', $results[0]['state'] );
		$this->assertSame( 200, $results[0]['value']->getStatusCode() );
		$this->assertSame( 'Response 1', (string) $results[0]['value']->getBody() );
		$this->assertSame( 'MISS', $results[0]['value']->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		$this->assertSame( 'fulfilled', $results[1]['state'] );
		$this->assertSame( 201, $results[1]['value']->getStatusCode() );
		$this->assertSame( 'Response 2', (string) $results[1]['value']->getBody() );
		$this->assertSame( 'MISS', $results[1]['value']->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );
	}

	public function testQueueRequestAndExecuteParallelWithFailures() {
		$this->mock_handler->append( new Response( 200, [], 'Success Response' ) );
		$this->mock_handler->append( new RequestException( 'Error', new Request( 'GET', '/test2' ) ) );
		$this->mock_handler->append( new ConnectException( 'Connection Error', new Request( 'POST', '/test3' ) ) );

		$this->http_client->queue_request( 'GET', '/test1' );
		$this->http_client->queue_request( 'GET', '/test2' );
		$this->http_client->queue_request( 'POST', '/test3' );

		$results = $this->http_client->execute_parallel();

		$this->assertCount( 3, $results );
		
		$this->assertSame( 'fulfilled', $results[0]['state'] );
		$this->assertSame( 200, $results[0]['value']->getStatusCode() );
		$this->assertSame( 'Success Response', (string) $results[0]['value']->getBody() );
		
		$this->assertSame( 'rejected', $results[1]['state'] );
		$this->assertInstanceOf( RequestException::class, $results[1]['reason'] );
		$this->assertSame( 'Error', $results[1]['reason']->getMessage() );
		
		$this->assertSame( 'rejected', $results[2]['state'] );
		$this->assertInstanceOf( ConnectException::class, $results[2]['reason'] );
		$this->assertSame( 'Connection Error', $results[2]['reason']->getMessage() );
	}

	public function testRepeatedGetCallsResultsInCacheHit() {
		// Set up the mock handler with only one response
		$this->mock_handler->append( new Response( 200, [], 'Cached Response' ) );

		$this->assertEquals( 1, $this->mock_handler->count(), 'The mock handler should have exactly one request' );

		// Make the first request
		$first_response = $this->http_client->request( 'GET', '/test' );

		$this->assertEquals( 0, $this->mock_handler->count(), 'The mock handler should be empty after the first request' );

		// Assert the first response
		$this->assertEquals( 200, $first_response->getStatusCode() );
		$this->assertEquals( 'Cached Response', (string) $first_response->getBody() );
		$this->assertEquals( 'MISS', $first_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		// Make the second request to the same endpoint
		$second_response = $this->http_client->request( 'GET', '/test' );

		// Assert the second response
		$this->assertEquals( 200, $second_response->getStatusCode() );
		$this->assertEquals( 'Cached Response', (string) $second_response->getBody() );
		$this->assertEquals( 'HIT', $second_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );
	}

	public function testRepeatedGetCallsWithQueryArgumentsResultsInCacheHit() {
		// Set up the mock handler with only one response
		$this->mock_handler->append( new Response( 200, [], 'Cached Response' ) );

		$this->assertEquals( 1, $this->mock_handler->count(), 'The mock handler should have exactly one request' );

		// Make the first request
		$first_response = $this->http_client->request( 'GET', '/test?arg1=value1&arg2=value2' );

		$this->assertEquals( 0, $this->mock_handler->count(), 'The mock handler should be empty after the first request' );

		// Assert the first response
		$this->assertEquals( 200, $first_response->getStatusCode() );
		$this->assertEquals( 'Cached Response', (string) $first_response->getBody() );
		$this->assertEquals( 'MISS', $first_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		// Make the second request to the same endpoint
		$second_response = $this->http_client->request( 'GET', '/test?arg1=value1&arg2=value2' );

		// Assert the second response
		$this->assertEquals( 200, $second_response->getStatusCode() );
		$this->assertEquals( 'Cached Response', (string) $second_response->getBody() );
		$this->assertEquals( 'HIT', $second_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );
	}

	public function testSubsequentGetCallsWithDifferentPathsResultsInCacheMiss() {
		// Set up the mock handler with two identical responses
		$this->mock_handler->append(
			new Response( 200, [], 'First Response' ),
			new Response( 200, [], 'Second Response' )
		);

		$this->assertEquals( 2, $this->mock_handler->count(), 'The mock handler should have exactly two requests' );

		// Make the first request
		$first_response = $this->http_client->request( 'GET', '/test0' );

		$this->assertEquals( 1, $this->mock_handler->count(), 'The mock handler should have exactly one request after the first request' );

		// Assert the first response
		$this->assertEquals( 200, $first_response->getStatusCode() );
		$this->assertEquals( 'First Response', (string) $first_response->getBody() );
		$this->assertEquals( 'MISS', $first_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		// Make the second request to the same endpoint
		$second_response = $this->http_client->request( 'GET', '/test1' );

		// Assert the second response
		$this->assertEquals( 200, $second_response->getStatusCode() );
		$this->assertEquals( 'Second Response', (string) $second_response->getBody() );
		$this->assertEquals( 'MISS', $second_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		$this->assertEquals( 0, $this->mock_handler->count(), 'The mock handler should be empty after the second request' );
	}

	public function testSubsequentGetCallsWithSamePathAndDifferentQueryArgumentsResultsInCacheMiss() {
		// Set up the mock handler with two identical responses
		$this->mock_handler->append(
			new Response( 200, [], 'First Response' ),
			new Response( 200, [], 'Second Response' )
		);

		$this->assertEquals( 2, $this->mock_handler->count(), 'The mock handler should have exactly two requests' );

		// Make the first request
		$first_response = $this->http_client->request( 'GET', '/test?arg1=value1&arg2=value2' );

		$this->assertEquals( 1, $this->mock_handler->count(), 'The mock handler should have exactly one request after the first request' );

		// Assert the first response
		$this->assertEquals( 200, $first_response->getStatusCode() );
		$this->assertEquals( 'First Response', (string) $first_response->getBody() );
		$this->assertEquals( 'MISS', $first_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		// Make the second request to the same endpoint
		$second_response = $this->http_client->request( 'GET', '/test?arg1=value1&arg2=value3' );

		// Assert the second response
		$this->assertEquals( 200, $second_response->getStatusCode() );
		$this->assertEquals( 'Second Response', (string) $second_response->getBody() );
		$this->assertEquals( 'MISS', $second_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		$this->assertEquals( 0, $this->mock_handler->count(), 'The mock handler should be empty after the second request' );
	}

	public function testRepeatedPostRequestsWithEmptyBodyResultsInCacheHit(): void {
		// Set up the mock handler with one response
		$this->mock_handler->append( new Response( 200, [], 'Cached Response' ) );

		$this->assertEquals( 1, $this->mock_handler->count(), 'The mock handler should have exactly one request' );

		// Make the first POST request with an empty body
		$first_response = $this->http_client->post( '/test' );

		$this->assertEquals( 0, $this->mock_handler->count(), 'The mock handler should be empty after the first request' );

		// Assert the first response
		$this->assertEquals( 200, $first_response->getStatusCode() );
		$this->assertEquals( 'Cached Response', (string) $first_response->getBody() );
		$this->assertEquals( 'MISS', $first_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		// Make the second POST request to the same endpoint with an empty body
		$second_response = $this->http_client->post( '/test' );

		// Assert the second response
		$this->assertEquals( 200, $second_response->getStatusCode() );
		$this->assertEquals( 'Cached Response', (string) $second_response->getBody() );
		$this->assertEquals( 'HIT', $second_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		$this->assertEquals( 0, $this->mock_handler->count(), 'The mock handler should still be empty after the second request' );
	}

	public function testRepeatedPostRequestsWithSameBodyResultsInCacheHit(): void {
		// Set up the mock handler with one response
		$this->mock_handler->append( new Response( 200, [], 'Cached Response' ) );

		$this->assertEquals( 1, $this->mock_handler->count(), 'The mock handler should have exactly one request' );

		// Make the first POST request
		$first_response = $this->http_client->post( '/test', [ 'body' => 'test data' ] );

		$this->assertEquals( 0, $this->mock_handler->count(), 'The mock handler should be empty after the first request' );

		// Assert the first response
		$this->assertEquals( 200, $first_response->getStatusCode() );
		$this->assertEquals( 'Cached Response', (string) $first_response->getBody() );
		$this->assertEquals( 'MISS', $first_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		// Make the second POST request to the same endpoint
		$second_response = $this->http_client->post( '/test', [ 'body' => 'test data' ] );

		// Assert the second response
		$this->assertEquals( 200, $second_response->getStatusCode() );
		$this->assertEquals( 'Cached Response', (string) $second_response->getBody() );
		$this->assertEquals( 'HIT', $second_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		$this->assertEquals( 0, $this->mock_handler->count(), 'The mock handler should still be empty after the second request' );
	}

	public function testRepeatedPostRequestsWithDifferentAuthorizationHeaderResultsInCacheMiss(): void {
		// Set up the mock handler with two responses
		$this->mock_handler->append(
			new Response( 200, [], 'First Response' ),
			new Response( 200, [], 'Second Response' )
		);

		$this->assertEquals( 2, $this->mock_handler->count(), 'The mock handler should have exactly two requests' );

		// Make the first POST request with an Authorization header
		$first_response = $this->http_client->post( '/test', [
			'headers' => [ 'Authorization' => 'Bearer token1' ],
			'body'    => 'test data',
		] );

		$this->assertEquals( 1, $this->mock_handler->count(), 'The mock handler should have one request left after the first request' );

		// Assert the first response
		$this->assertEquals( 200, $first_response->getStatusCode() );
		$this->assertEquals( 'First Response', (string) $first_response->getBody() );
		$this->assertEquals( 'MISS', $first_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		// Make the second POST request to the same endpoint but with a different Authorization header
		$second_response = $this->http_client->post( '/test', [
			'headers' => [ 'Authorization' => 'Bearer token2' ],
			'body'    => 'test data',
		] );

		// Assert the second response
		$this->assertEquals( 200, $second_response->getStatusCode() );
		$this->assertEquals( 'Second Response', (string) $second_response->getBody() );
		$this->assertEquals( 'MISS', $second_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		$this->assertEquals( 0, $this->mock_handler->count(), 'The mock handler should be empty after the second request' );
	}

	public function testRepeatedPostRequestsWithDifferentBodyResultsInCacheMiss(): void {
		// Set up the mock handler with two responses
		$this->mock_handler->append(
			new Response( 200, [], 'First Response' ),
			new Response( 200, [], 'Second Response' )
		);

		$this->assertEquals( 2, $this->mock_handler->count(), 'The mock handler should have exactly two requests' );

		// Make the first POST request
		$first_response = $this->http_client->post( '/test', [ 'body' => 'first data' ] );

		$this->assertEquals( 1, $this->mock_handler->count(), 'The mock handler should have one request left after the first request' );

		// Assert the first response
		$this->assertEquals( 200, $first_response->getStatusCode() );
		$this->assertEquals( 'First Response', (string) $first_response->getBody() );
		$this->assertEquals( 'MISS', $first_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		// Make the second POST request to the same endpoint but with different body
		$second_response = $this->http_client->post( '/test', [ 'body' => 'second data' ] );

		// Assert the second response
		$this->assertEquals( 200, $second_response->getStatusCode() );
		$this->assertEquals( 'Second Response', (string) $second_response->getBody() );
		$this->assertEquals( 'MISS', $second_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		$this->assertEquals( 0, $this->mock_handler->count(), 'The mock handler should be empty after the second request' );
	}

	public function testRepeatedPostRequestsWithDifferentGraphqlMutationInBodyResultsInCacheMiss(): void {
		$this->mock_handler->append(
			new Response( 200, [], 'First Response' ),
			new Response( 200, [], 'Second Response' )
		);

		$this->assertEquals( 2, $this->mock_handler->count(), 'The mock handler should have exactly two requests' );

		$first_mutation = '
			mutation CreatePost($title: String!) {
				createPost(input: {title: $title}) {
					post {
						id
						title
					}
				}
			}
		';

		$second_mutation = '
			mutation UpdatePost($id: ID!, $title: String!) {
				updatePost(input: {id: $id, title: $title}) {
					post {
						id
						title
					}
				}
			}
		';

		$variables = [
			'title' => 'Test Title',
		];

		// Make the first POST request
		$first_response = $this->http_client->post( '/graphql', [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'json'    => [
				'query'     => $first_mutation,
				'variables' => $variables,
			],
		] );

		$this->assertEquals( 1, $this->mock_handler->count(), 'The mock handler should have one request left after the first request' );

		// Assert the first response
		$this->assertEquals( 200, $first_response->getStatusCode() );
		$this->assertEquals( 'First Response', (string) $first_response->getBody() );
		$this->assertEquals( 'MISS', $first_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		// Make the second POST request with a different GraphQL mutation
		$second_response = $this->http_client->post( '/graphql', [
			'json' => [
				'query'     => $second_mutation,
				'variables' => array_merge( $variables, [ 'id' => '1' ] ),
			],
		] );

		// Assert the second response
		$this->assertEquals( 200, $second_response->getStatusCode() );
		$this->assertEquals( 'Second Response', (string) $second_response->getBody() );
		$this->assertEquals( 'MISS', $second_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		$this->assertEquals( 0, $this->mock_handler->count(), 'The mock handler should be empty after the second request' );
	}

	public function testRepeatedPostRequestsWithSameGraphqlQueryInBodyResultsInCacheHit(): void {
		$this->mock_handler->append( new Response( 200, [], 'First Response' ) );

		$query = '
			query GetPost($id: ID!) {
				post(id: $id) {
					id
					title
					content
				}
			}
		';

		$variables = [
			'id' => '1',
		];

		// Make the first POST request
		$first_response = $this->http_client->post( '/graphql', [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'json'    => [
				'query'     => $query,
				'variables' => $variables,
			],
		] );

		$this->assertEquals( 0, $this->mock_handler->count(), 'The mock handler should be empty after the first request' );

		// Assert the first response
		$this->assertEquals( 200, $first_response->getStatusCode() );
		$this->assertEquals( 'First Response', (string) $first_response->getBody() );
		$this->assertEquals( 'MISS', $first_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		// Make the second POST request with the same GraphQL query
		$second_response = $this->http_client->post( '/graphql', [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'json'    => [
				'query'     => $query,
				'variables' => $variables,
			],
		] );

		// Assert the second response
		$this->assertEquals( 200, $second_response->getStatusCode() );
		$this->assertEquals( 'First Response', (string) $second_response->getBody() );
		$this->assertEquals( 'HIT', $second_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		$this->assertEquals( 0, $this->mock_handler->count(), 'The mock handler should be empty after the second request' );
	}

	public function testRepeatedPostRequestsWithDifferentGraphqlQueryInBodyResultsInCacheMiss(): void {
		$this->mock_handler->append(
			new Response( 200, [], 'First Response' ),
			new Response( 200, [], 'Second Response' )
		);

		$first_query = '
			query GetPost($id: ID!) {
				post(id: $id) {
					id
					title
				}
			}
		';

		$second_query = '
			query GetPost($id: ID!) {
				post(id: $id) {
					id
					title
					content
				}
			}
		';

		$variables = [
			'id' => '1',
		];

		// Make the first POST request
		$first_response = $this->http_client->post( '/graphql', [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'json'    => [
				'query'     => $first_query,
				'variables' => $variables,
			],
		] );

		$this->assertEquals( 1, $this->mock_handler->count(), 'The mock handler should have one response left after the first request' );

		// Assert the first response
		$this->assertEquals( 200, $first_response->getStatusCode() );
		$this->assertEquals( 'First Response', (string) $first_response->getBody() );
		$this->assertEquals( 'MISS', $first_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		// Make the second POST request with a different GraphQL query
		$second_response = $this->http_client->post( '/graphql', [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'json'    => [
				'query'     => $second_query,
				'variables' => $variables,
			],
		] );

		// Assert the second response
		$this->assertEquals( 200, $second_response->getStatusCode() );
		$this->assertEquals( 'Second Response', (string) $second_response->getBody() );
		$this->assertEquals( 'MISS', $second_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		$this->assertEquals( 0, $this->mock_handler->count(), 'The mock handler should be empty after the second request' );
	}

	public function testRepeatedPostRequestsWithSameGraphqlQueryAndDifferentVariablesInBodyResultsInCacheMiss(): void {
		$this->mock_handler->append(
			new Response( 200, [], 'First Response' ),
			new Response( 200, [], 'Second Response' )
		);

		$query = '
			query GetPost($id: ID!) {
				post(id: $id) {
					id
					title
					content
				}
			}
		';

		$first_variables = [
			'id' => '1',
		];

		$second_variables = [
			'id' => '2',
		];

		// Make the first POST request
		$first_response = $this->http_client->post( '/graphql', [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'json'    => [
				'query'     => $query,
				'variables' => $first_variables,
			],
		] );

		$this->assertEquals( 1, $this->mock_handler->count(), 'The mock handler should have one response left after the first request' );

		// Assert the first response
		$this->assertEquals( 200, $first_response->getStatusCode() );
		$this->assertEquals( 'First Response', (string) $first_response->getBody() );
		$this->assertEquals( 'MISS', $first_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		// Make the second POST request with the same GraphQL query but different variables
		$second_response = $this->http_client->post( '/graphql', [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'json'    => [
				'query'     => $query,
				'variables' => $second_variables,
			],
		] );

		// Assert the second response
		$this->assertEquals( 200, $second_response->getStatusCode() );
		$this->assertEquals( 'Second Response', (string) $second_response->getBody() );
		$this->assertEquals( 'MISS', $second_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		$this->assertEquals( 0, $this->mock_handler->count(), 'The mock handler should be empty after the second request' );
	}
}
