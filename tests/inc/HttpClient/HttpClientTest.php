<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\HttpClient;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Kevinrob\GuzzleCache\CacheEntry;
use Kevinrob\GuzzleCache\Storage\VolatileRuntimeStorage;
use RemoteDataBlocks\HttpClient\RdbCacheMiddleware;
use RemoteDataBlocks\HttpClient\RdbCacheStrategy;
use RemoteDataBlocks\HttpClient\HttpClient;

class HttpClientTest extends TestCase {
	private Client $client;
	private HttpClient $http_client;
	private MockHandler $mock_handler;

	protected function setUp(): void {
		parent::setUp();

		$this->mock_handler = new MockHandler();
		$handler = HandlerStack::create( $this->mock_handler );

		$handler->push( new RdbCacheMiddleware( new RdbCacheStrategy( new VolatileRuntimeStorage() ) ) );
		$client = new Client( [ 'handler' => $handler ] );

		$this->client = $client;
		$this->http_client = HttpClient::instance();
	}

	public function testSingleton(): void {
		$client = HttpClient::instance();
		$this->assertInstanceOf( HttpClient::class, $client );
	}

	public function testRequest(): void {
		$this->mock_handler->append( new Response( 200, [], 'Success' ) );
		$response = $this->http_client->request( 'GET', '/test', [], $this->client );
		$this->assertSame( 200, $response->getStatusCode() );
		$this->assertSame( 'Success', (string) $response->getBody() );
		$this->assertSame( 'MISS', $response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );
	}

	public function testGet(): void {
		$this->mock_handler->append( new Response( 200, [], 'GET Success' ) );
		$response = $this->http_client->request( 'GET', '/test', [], $this->client );
		$this->assertSame( 200, $response->getStatusCode() );
		$this->assertSame( 'GET Success', (string) $response->getBody() );
		$this->assertSame( 'MISS', $response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );
	}

	public function testPost(): void {
		$this->mock_handler->append( new Response( 201, [], 'POST Success' ) );
		$response = $this->http_client->request( 'POST', '/test', [], $this->client );
		$this->assertSame( 201, $response->getStatusCode() );
		$this->assertSame( 'POST Success', (string) $response->getBody() );
		$this->assertSame( 'MISS', $response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );
	}

	public function testRepeatedGetCallsResultsInCacheHit(): void {
		// Set up the mock handler with only one response
		$this->mock_handler->append( new Response( 200, [], 'Cached Response' ) );

		$this->assertEquals( 1, $this->mock_handler->count(), 'The mock handler should have exactly one request' );

		// Make the first request
		$first_response = $this->http_client->request( 'GET', '/test', [], $this->client );

		$this->assertEquals( 0, $this->mock_handler->count(), 'The mock handler should be empty after the first request' );

		// Assert the first response
		$this->assertEquals( 200, $first_response->getStatusCode() );
		$this->assertEquals( 'Cached Response', (string) $first_response->getBody() );
		$this->assertEquals( 'MISS', $first_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		// Make the second request to the same endpoint
		$second_response = $this->http_client->request( 'GET', '/test', [], $this->client );

		// Assert the second response
		$this->assertEquals( 200, $second_response->getStatusCode() );
		$this->assertEquals( 'Cached Response', (string) $second_response->getBody() );
		$this->assertEquals( 'HIT', $second_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );
	}

	public function testRepeatedGetCallsWithQueryArgumentsResultsInCacheHit(): void {
		// Set up the mock handler with only one response
		$this->mock_handler->append( new Response( 200, [], 'Cached Response' ) );

		$this->assertEquals( 1, $this->mock_handler->count(), 'The mock handler should have exactly one request' );

		// Make the first request
		$first_response = $this->http_client->request( 'GET', '/test?arg1=value1&arg2=value2', [], $this->client );

		$this->assertEquals( 0, $this->mock_handler->count(), 'The mock handler should be empty after the first request' );

		// Assert the first response
		$this->assertEquals( 200, $first_response->getStatusCode() );
		$this->assertEquals( 'Cached Response', (string) $first_response->getBody() );
		$this->assertEquals( 'MISS', $first_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		// Make the second request to the same endpoint
		$second_response = $this->http_client->request( 'GET', '/test?arg1=value1&arg2=value2', [], $this->client );

		// Assert the second response
		$this->assertEquals( 200, $second_response->getStatusCode() );
		$this->assertEquals( 'Cached Response', (string) $second_response->getBody() );
		$this->assertEquals( 'HIT', $second_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );
	}

	public function testSubsequentGetCallsWithDifferentPathsResultsInCacheMiss(): void {
		// Set up the mock handler with two identical responses
		$this->mock_handler->append(
			new Response( 200, [], 'First Response' ),
			new Response( 200, [], 'Second Response' )
		);

		$this->assertEquals( 2, $this->mock_handler->count(), 'The mock handler should have exactly two requests' );

		// Make the first request
		$first_response = $this->http_client->request( 'GET', '/test0', [], $this->client );

		$this->assertEquals( 1, $this->mock_handler->count(), 'The mock handler should have exactly one request after the first request' );

		// Assert the first response
		$this->assertEquals( 200, $first_response->getStatusCode() );
		$this->assertEquals( 'First Response', (string) $first_response->getBody() );
		$this->assertEquals( 'MISS', $first_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		// Make the second request to the same endpoint
		$second_response = $this->http_client->request( 'GET', '/test1', [], $this->client );

		// Assert the second response
		$this->assertEquals( 200, $second_response->getStatusCode() );
		$this->assertEquals( 'Second Response', (string) $second_response->getBody() );
		$this->assertEquals( 'MISS', $second_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		$this->assertEquals( 0, $this->mock_handler->count(), 'The mock handler should be empty after the second request' );
	}

	public function testSubsequentGetCallsWithSamePathAndDifferentQueryArgumentsResultsInCacheMiss(): void {
		// Set up the mock handler with two identical responses
		$this->mock_handler->append(
			new Response( 200, [], 'First Response' ),
			new Response( 200, [], 'Second Response' )
		);

		$this->assertEquals( 2, $this->mock_handler->count(), 'The mock handler should have exactly two requests' );

		// Make the first request
		$first_response = $this->http_client->request( 'GET', '/test?arg1=value1&arg2=value2', [], $this->client );

		$this->assertEquals( 1, $this->mock_handler->count(), 'The mock handler should have exactly one request after the first request' );

		// Assert the first response
		$this->assertEquals( 200, $first_response->getStatusCode() );
		$this->assertEquals( 'First Response', (string) $first_response->getBody() );
		$this->assertEquals( 'MISS', $first_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		// Make the second request to the same endpoint
		$second_response = $this->http_client->request( 'GET', '/test?arg1=value1&arg2=value3', [], $this->client );

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
		$first_response = $this->http_client->request( 'POST', '/test', [], $this->client );

		$this->assertEquals( 0, $this->mock_handler->count(), 'The mock handler should be empty after the first request' );

		// Assert the first response
		$this->assertEquals( 200, $first_response->getStatusCode() );
		$this->assertEquals( 'Cached Response', (string) $first_response->getBody() );
		$this->assertEquals( 'MISS', $first_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		// Make the second POST request to the same endpoint with an empty body
		$second_response = $this->http_client->request( 'POST', '/test', [], $this->client );

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
		$first_response = $this->http_client->request( 'POST', '/test', [ 'body' => 'test data' ], $this->client );

		$this->assertEquals( 0, $this->mock_handler->count(), 'The mock handler should be empty after the first request' );

		// Assert the first response
		$this->assertEquals( 200, $first_response->getStatusCode() );
		$this->assertEquals( 'Cached Response', (string) $first_response->getBody() );
		$this->assertEquals( 'MISS', $first_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		// Make the second POST request to the same endpoint
		$second_response = $this->http_client->request( 'POST', '/test', [ 'body' => 'test data' ], $this->client );

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
		$first_response = $this->http_client->request( 'POST', '/test', [
			'headers' => [ 'Authorization' => 'Bearer token1' ],
			'body' => 'test data',
		], $this->client );

		$this->assertEquals( 1, $this->mock_handler->count(), 'The mock handler should have one request left after the first request' );

		// Assert the first response
		$this->assertEquals( 200, $first_response->getStatusCode() );
		$this->assertEquals( 'First Response', (string) $first_response->getBody() );
		$this->assertEquals( 'MISS', $first_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		// Make the second POST request to the same endpoint but with a different Authorization header
		$second_response = $this->http_client->request( 'POST', '/test', [
			'headers' => [ 'Authorization' => 'Bearer token2' ],
			'body' => 'test data',
		], $this->client );

		// Assert the second response
		$this->assertEquals( 200, $second_response->getStatusCode() );
		$this->assertEquals( 'Second Response', (string) $second_response->getBody() );
		$this->assertEquals( 'MISS', $second_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		$this->assertEquals( 0, $this->mock_handler->count(), 'The mock handler should be empty after the second request' );
	}

	public function testUnconfiguredCustomHeaderWithDifferentValuesResultsInCacheHit(): void {
		$this->mock_handler->append(
			new Response( 200, [], 'Cached Response' ),
			new Response( 200, [], 'Uncached Response' )
		);

		$first_response = $this->http_client->request( 'GET', '/test', [
			'headers' => [ 'X-Tenant-ID' => 'first-tenant' ],
		], $this->client );
		$second_response = $this->http_client->request( 'GET', '/test', [
			'headers' => [ 'X-Tenant-ID' => 'second-tenant' ],
		], $this->client );

		$this->assertSame( 'Cached Response', (string) $first_response->getBody() );
		$this->assertSame( RdbCacheMiddleware::HEADER_CACHE_MISS, $first_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );
		$this->assertSame( 'Cached Response', (string) $second_response->getBody() );
		$this->assertSame( RdbCacheMiddleware::HEADER_CACHE_HIT, $second_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );
		$this->assertSame( 1, $this->mock_handler->count(), 'Only one response should be consumed when an unconfigured header value differs' );
	}

	public function testConfiguredCustomHeaderWithDifferentValuesResultsInCacheMiss(): void {
		$this->mock_handler->append(
			new Response( 200, [], 'First Response' ),
			new Response( 200, [], 'Second Response' )
		);

		$first_response = $this->http_client->request( 'GET', '/test', [
			'headers' => [
				RdbCacheStrategy::CACHE_KEY_REQUEST_HEADERS_REQUEST_HEADER => [ 'Authorization', 'Cache-Control', 'X-Api-Key' ],
				'X-Api-Key' => 'first-api-key',
			],
		], $this->client );
		$second_response = $this->http_client->request( 'GET', '/test', [
			'headers' => [
				RdbCacheStrategy::CACHE_KEY_REQUEST_HEADERS_REQUEST_HEADER => [ 'Authorization', 'Cache-Control', 'X-Api-Key' ],
				'X-Api-Key' => 'second-api-key',
			],
		], $this->client );

		$this->assertSame( 'First Response', (string) $first_response->getBody() );
		$this->assertSame( RdbCacheMiddleware::HEADER_CACHE_MISS, $first_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );
		$this->assertSame( 'Second Response', (string) $second_response->getBody() );
		$this->assertSame( RdbCacheMiddleware::HEADER_CACHE_MISS, $second_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );
		$this->assertSame( 0, $this->mock_handler->count(), 'Both responses should be consumed when the custom header values differ' );
	}

	public function testConfiguredCustomHeaderNameIsCaseInsensitive(): void {
		$this->mock_handler->append(
			new Response( 200, [], 'First Response' ),
			new Response( 200, [], 'Second Response' )
		);

		$first_response = $this->http_client->request( 'GET', '/test', [
			'headers' => [
				RdbCacheStrategy::CACHE_KEY_REQUEST_HEADERS_REQUEST_HEADER => [ 'X-Api-Key' ],
				'x-api-key' => 'first-api-key',
			],
		], $this->client );
		$second_response = $this->http_client->request( 'GET', '/test', [
			'headers' => [
				RdbCacheStrategy::CACHE_KEY_REQUEST_HEADERS_REQUEST_HEADER => [ 'X-Api-Key' ],
				'x-api-key' => 'second-api-key',
			],
		], $this->client );

		$this->assertSame( 'First Response', (string) $first_response->getBody() );
		$this->assertSame( 'Second Response', (string) $second_response->getBody() );
		$this->assertSame( RdbCacheMiddleware::HEADER_CACHE_MISS, $second_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );
		$this->assertSame( 0, $this->mock_handler->count(), 'Both responses should be consumed regardless of custom header casing' );
	}

	public function testCacheKeyRequestHeaderMetadataIsNotSentToRequestHandler(): void {
		$transactions = [];
		$mock_handler = new MockHandler( [ new Response( 200, [], 'Success' ) ] );
		$handler_stack = HandlerStack::create( $mock_handler );
		$handler_stack->push( new RdbCacheMiddleware( new RdbCacheStrategy( new VolatileRuntimeStorage() ) ), 'cache' );
		$handler_stack->push( Middleware::history( $transactions ), 'history' );
		$client = new Client( [ 'handler' => $handler_stack ] );

		$this->http_client->request( 'GET', '/test', [
			'headers' => [
				RdbCacheStrategy::CACHE_KEY_REQUEST_HEADERS_REQUEST_HEADER => [ 'X-Api-Key' ],
				'X-Api-Key' => 'secret',
			],
		], $client );

		$this->assertCount( 1, $transactions );
		$this->assertSame( 'secret', $transactions[0]['request']->getHeaderLine( 'X-Api-Key' ) );
		$this->assertFalse( $transactions[0]['request']->hasHeader( RdbCacheStrategy::CACHE_KEY_REQUEST_HEADERS_REQUEST_HEADER ) );
	}

	public function testCacheTtlMetadataIsNotSentToRequestHandler(): void {
		$transactions = [];
		$mock_handler = new MockHandler( [ new Response( 200, [], 'Success' ) ] );
		$handler_stack = HandlerStack::create( $mock_handler );
		$handler_stack->push( new RdbCacheMiddleware( new RdbCacheStrategy( new VolatileRuntimeStorage() ) ), 'cache' );
		$handler_stack->push( Middleware::history( $transactions ), 'history' );
		$client = new Client( [ 'handler' => $handler_stack ] );

		$this->http_client->request( 'GET', '/test', [
			'headers' => [
				RdbCacheStrategy::CACHE_TTL_REQUEST_HEADER => 600,
			],
		], $client );

		$this->assertCount( 1, $transactions );
		$this->assertFalse( $transactions[0]['request']->hasHeader( RdbCacheStrategy::CACHE_TTL_REQUEST_HEADER ) );
	}

	public function testCacheKeyRequestHeaderMetadataIsNotStoredInCacheEntry(): void {
		$storage = new VolatileRuntimeStorage();
		$strategy = new RdbCacheStrategy( $storage );
		$request = new Request( 'GET', 'https://example.com/test', [
			RdbCacheStrategy::CACHE_KEY_REQUEST_HEADERS_REQUEST_HEADER => [ 'X-Api-Key' ],
			'X-Api-Key' => 'secret',
		] );

		$strategy->cache( $request, new Response( 200 ) );
		$cache_entry = $strategy->fetch( $request );

		$this->assertInstanceOf( CacheEntry::class, $cache_entry );
		$this->assertFalse( $cache_entry->getOriginalRequest()->hasHeader( RdbCacheStrategy::CACHE_KEY_REQUEST_HEADERS_REQUEST_HEADER ) );
	}

	public function testCacheStrategyUpdateReplacesCachedResponse(): void {
		$strategy = new RdbCacheStrategy( new VolatileRuntimeStorage() );
		$request = new Request( 'GET', 'https://example.com/test' );

		$this->assertTrue( $strategy->cache( $request, new Response( 200, [], 'Initial Response' ) ) );
		$this->assertTrue( $strategy->update( $request, new Response( 200, [], 'Updated Response' ) ) );

		$cache_entry = $strategy->fetch( $request );
		$this->assertInstanceOf( CacheEntry::class, $cache_entry );
		$this->assertSame( 'Updated Response', (string) $cache_entry->getResponse()->getBody() );
	}

	public function testCacheStrategyDeleteRemovesCachedResponse(): void {
		$strategy = new RdbCacheStrategy( new VolatileRuntimeStorage() );
		$request = new Request( 'GET', 'https://example.com/test' );

		$this->assertTrue( $strategy->cache( $request, new Response( 200 ) ) );
		$this->assertTrue( $strategy->delete( $request ) );
		$this->assertNull( $strategy->fetch( $request ) );
	}

	public function testCacheStrategyAddsWarningHeaderToCachedResponse(): void {
		$strategy = new RdbCacheStrategy( new VolatileRuntimeStorage() );
		$request = new Request( 'GET', 'https://example.com/test' );

		$this->assertTrue( $strategy->cache( $request, new Response( 200 ) ) );

		$cache_entry = $strategy->fetch( $request );
		$this->assertInstanceOf( CacheEntry::class, $cache_entry );
		$this->assertStringContainsString(
			'Cached although the response headers indicate not to do it!',
			$cache_entry->getResponse()->getHeaderLine( 'Warning' )
		);
	}

	public function testRepeatedPostRequestsWithDifferentBodyResultsInCacheMiss(): void {
		// Set up the mock handler with two responses
		$this->mock_handler->append(
			new Response( 200, [], 'First Response' ),
			new Response( 200, [], 'Second Response' )
		);

		$this->assertEquals( 2, $this->mock_handler->count(), 'The mock handler should have exactly two requests' );

		// Make the first POST request
		$first_response = $this->http_client->request( 'POST', '/test', [ 'body' => 'first data' ], $this->client );

		$this->assertEquals( 1, $this->mock_handler->count(), 'The mock handler should have one request left after the first request' );

		// Assert the first response
		$this->assertEquals( 200, $first_response->getStatusCode() );
		$this->assertEquals( 'First Response', (string) $first_response->getBody() );
		$this->assertEquals( 'MISS', $first_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		// Make the second POST request to the same endpoint but with different body
		$second_response = $this->http_client->request( 'POST', '/test', [ 'body' => 'second data' ], $this->client );

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
		$first_response = $this->http_client->request( 'POST', '/graphql', [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'json' => [
				'query' => $first_mutation,
				'variables' => $variables,
			],
		], $this->client );

		$this->assertEquals( 1, $this->mock_handler->count(), 'The mock handler should have one request left after the first request' );

		// Assert the first response
		$this->assertEquals( 200, $first_response->getStatusCode() );
		$this->assertEquals( 'First Response', (string) $first_response->getBody() );
		$this->assertEquals( 'MISS', $first_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		// Make the second POST request with a different GraphQL mutation
		$second_response = $this->http_client->request( 'POST', '/graphql', [
			'json' => [
				'query' => $second_mutation,
				'variables' => array_merge( $variables, [ 'id' => '1' ] ),
			],
		], $this->client );

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
		$first_response = $this->http_client->request( 'POST', '/graphql', [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'json' => [
				'query' => $query,
				'variables' => $variables,
			],
		], $this->client );

		$this->assertEquals( 0, $this->mock_handler->count(), 'The mock handler should be empty after the first request' );

		// Assert the first response
		$this->assertEquals( 200, $first_response->getStatusCode() );
		$this->assertEquals( 'First Response', (string) $first_response->getBody() );
		$this->assertEquals( 'MISS', $first_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		// Make the second POST request with the same GraphQL query
		$second_response = $this->http_client->request( 'POST', '/graphql', [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'json' => [
				'query' => $query,
				'variables' => $variables,
			],
		], $this->client );

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
		$first_response = $this->http_client->request( 'POST', '/graphql', [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'json' => [
				'query' => $first_query,
				'variables' => $variables,
			],
		], $this->client );

		$this->assertEquals( 1, $this->mock_handler->count(), 'The mock handler should have one response left after the first request' );

		// Assert the first response
		$this->assertEquals( 200, $first_response->getStatusCode() );
		$this->assertEquals( 'First Response', (string) $first_response->getBody() );
		$this->assertEquals( 'MISS', $first_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		// Make the second POST request with a different GraphQL query
		$second_response = $this->http_client->request( 'POST', '/graphql', [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'json' => [
				'query' => $second_query,
				'variables' => $variables,
			],
		], $this->client );

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
		$first_response = $this->http_client->request( 'POST', '/graphql', [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'json' => [
				'query' => $query,
				'variables' => $first_variables,
			],
		], $this->client );

		$this->assertEquals( 1, $this->mock_handler->count(), 'The mock handler should have one response left after the first request' );

		// Assert the first response
		$this->assertEquals( 200, $first_response->getStatusCode() );
		$this->assertEquals( 'First Response', (string) $first_response->getBody() );
		$this->assertEquals( 'MISS', $first_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		// Make the second POST request with the same GraphQL query but different variables
		$second_response = $this->http_client->request( 'POST', '/graphql', [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'json' => [
				'query' => $query,
				'variables' => $second_variables,
			],
		], $this->client );

		// Assert the second response
		$this->assertEquals( 200, $second_response->getStatusCode() );
		$this->assertEquals( 'Second Response', (string) $second_response->getBody() );
		$this->assertEquals( 'MISS', $second_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		$this->assertEquals( 0, $this->mock_handler->count(), 'The mock handler should be empty after the second request' );
	}

	public function testRepeatedErrorsWithSameCacheKeyResultInCacheHit(): void {
		// Set up the mock handler with one response
		$this->mock_handler->append( new Response( 500, [], 'Error response' ) );
		$this->mock_handler->append( new Response( 200, [], 'Success response' ) );

		$this->assertEquals( 2, $this->mock_handler->count(), 'The mock handler should have two requests' );

		// Make the first request
		$first_response = $this->http_client->request( 'GET', '/test', [], $this->client );

		$this->assertEquals( 1, $this->mock_handler->count(), 'The mock handler should have one request left after the first request' );

		// Assert the first response
		$this->assertEquals( 500, $first_response->getStatusCode() );
		$this->assertEquals( 'Error response', (string) $first_response->getBody() );
		$this->assertEquals( 'MISS', $first_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		// Make the second request to the same endpoint
		$second_response = $this->http_client->request( 'GET', '/test', [], $this->client );

		// Assert the second response
		$this->assertEquals( 500, $second_response->getStatusCode() );
		$this->assertEquals( 'Error response', (string) $second_response->getBody() );
		$this->assertEquals( 'HIT', $second_response->getHeaderLine( RdbCacheMiddleware::HEADER_CACHE_INFO ) );

		$this->assertEquals( 1, $this->mock_handler->count(), 'The mock handler should still have one request left after the second request' );
	}

	public function testProvideDefaultUserAgentSetsDefaultWhenNoneExists(): void {
		// Test that provideDefaultUserAgent sets the default User-Agent when none exists
		$http_client = HttpClient::instance();

		// Create a request without User-Agent header
		$request = new \GuzzleHttp\Psr7\Request( 'GET', '/test' );

		// Apply the function
		$result = $http_client->provide_default_user_agent( $request );

		// Should have added User-Agent header
		$this->assertTrue( $result->hasHeader( 'User-Agent' ) );
		$this->assertSame( 'WordPress Remote Data Blocks/1.0', $result->getHeaderLine( 'User-Agent' ) );
	}

	public function testProvideDefaultUserAgentPreservesExistingUserAgent(): void {
		// Test that provideDefaultUserAgent preserves existing User-Agent headers
		$http_client = HttpClient::instance();

		// Create a request with custom User-Agent header
		$request = new \GuzzleHttp\Psr7\Request( 'GET', '/test', [ 'User-Agent' => 'CustomApp/2.0' ] );

		// Apply the function
		$result = $http_client->provide_default_user_agent( $request );

		// Should preserve custom User-Agent header
		$this->assertTrue( $result->hasHeader( 'User-Agent' ) );
		$this->assertSame( 'CustomApp/2.0', $result->getHeaderLine( 'User-Agent' ) );
	}
}
