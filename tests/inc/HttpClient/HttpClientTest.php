<?php

namespace RemoteDataBlocks\Tests\HttpClient;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use RemoteDataBlocks\HttpClient\HttpClient;

class HttpClientTest extends TestCase {
	private $http_client;
	private $mock_handler;

	protected function setUp(): void {
		parent::setUp();
		$this->mock_handler = new MockHandler();
		$handler            = HandlerStack::create( $this->mock_handler );
		$client             = new Client( [ 'handler' => $handler ] );

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
		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 'Success', (string) $response->getBody() );
	}

	public function testGet() {
		$this->mock_handler->append( new Response( 200, [], 'GET Success' ) );
		$response = $this->http_client->get( '/test' );
		$this->assertEquals( 200, $response->getStatusCode() );
		$this->assertEquals( 'GET Success', (string) $response->getBody() );
	}

	public function testPost() {
		$this->mock_handler->append( new Response( 201, [], 'POST Success' ) );
		$response = $this->http_client->post( '/test' );
		$this->assertEquals( 201, $response->getStatusCode() );
		$this->assertEquals( 'POST Success', (string) $response->getBody() );
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
		$this->assertEquals( 120000, $delay );

		$response = new Response( 429, [ 'Retry-After' => ( new \DateTime( '+2 minutes' ) )->format( \DateTime::RFC7231 ) ] );
		$delay    = HttpClient::retry_delay( 1, $response );
		$this->assertGreaterThan( 119000, $delay );
		$this->assertLessThan( 121000, $delay );

		$response = new Response( 500 );
		$delay    = HttpClient::retry_delay( 2, $response );
		$this->assertEquals( 2000, $delay );
	}

	public function testQueueRequestAndExecuteParallel() {
		$this->mock_handler->append( new Response( 200, [], 'Response 1' ) );
		$this->mock_handler->append( new Response( 201, [], 'Response 2' ) );

		$this->http_client->queue_request( 'GET', '/test1' );
		$this->http_client->queue_request( 'POST', '/test2' );

		$results = $this->http_client->execute_parallel();

		$this->assertCount( 2, $results );
		$this->assertEquals( 'fulfilled', $results[0]['state'] );
		$this->assertEquals( 200, $results[0]['value']->getStatusCode() );
		$this->assertEquals( 'Response 1', (string) $results[0]['value']->getBody() );
		$this->assertEquals( 'fulfilled', $results[1]['state'] );
		$this->assertEquals( 201, $results[1]['value']->getStatusCode() );
		$this->assertEquals( 'Response 2', (string) $results[1]['value']->getBody() );
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
		
		$this->assertEquals( 'fulfilled', $results[0]['state'] );
		$this->assertEquals( 200, $results[0]['value']->getStatusCode() );
		$this->assertEquals( 'Success Response', (string) $results[0]['value']->getBody() );
		
		$this->assertEquals( 'rejected', $results[1]['state'] );
		$this->assertInstanceOf( RequestException::class, $results[1]['reason'] );
		$this->assertEquals( 'Error', $results[1]['reason']->getMessage() );
		
		$this->assertEquals( 'rejected', $results[2]['state'] );
		$this->assertInstanceOf( ConnectException::class, $results[2]['reason'] );
		$this->assertEquals( 'Connection Error', $results[2]['reason']->getMessage() );
	}
}
