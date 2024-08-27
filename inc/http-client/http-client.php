<?php

namespace RemoteDataBlocks;

use Exception;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise\Utils;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\KeyValueHttpHeader;
use Kevinrob\GuzzleCache\Storage\WordPressObjectCacheStorage;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use RemoteDataBlocks\Logging\LoggerManager;

defined( 'ABSPATH' ) || exit();

class HttpClient {
	private const MAX_RETRIES                        = 3;
	private const CACHE_TTL_IN_SECONDS               = 60;
	private const WP_OBJECT_CACHE_GROUP              = 'remote-data-blocks';
	private const CACHE_INVALIDATING_REQUEST_HEADERS = [ 'Authorization', 'Cache-Control' ];

	/**
	 * @var Client
	 */
	public $client;

	/**
	 * @var string
	 */
	private $base_uri;

	/**
	 * @var array
	 */
	private $headers = [];

	/**
	 * @var array
	 */
	private $options = [];

	/**
	 * @var HandlerStack
	 */
	private $handler_stack;

	/**
	 * @var array
	 */
	private $default_options = [
		'timeout' => 3,
		'headers' => [
			'User-Agent' => 'WordPress Remote Data Blocks/1.0',
		],
	];

	/**
	 * @var array
	 */
	private $queued_requests = [];

	/**
	 * Initialize the HTTP client.
	 *
	 * @param string $base_uri
	 * @param array  $headers
	 * @param array  $options
	 */
	public function init( string $base_uri, array $headers = [], array $options = [] ): void {
		$this->base_uri = $base_uri;
		$this->headers  = $headers;
		$this->options  = $options;

		$this->handler_stack = HandlerStack::create(
			new CurlHandler(
				// low-level curl options go here
			)
		);

		$this->handler_stack->push( Middleware::retry(
			self::class . '::retry_decider',
			self::class . '::retry_delay'
		) );

		$this->handler_stack->push( Middleware::mapRequest( function ( RequestInterface $request ) {
			foreach ( $this->headers as $header => $value ) {
				$request = $request->withHeader( $header, $value );
			}

			return $request;
		} ) );

		$this->handler_stack->push(
			new CacheMiddleware(
				new GreedyCacheStrategy(
					new WordPressObjectCacheStorage( self::WP_OBJECT_CACHE_GROUP ),
					self::CACHE_TTL_IN_SECONDS,
					new KeyValueHttpHeader( self::CACHE_INVALIDATING_REQUEST_HEADERS )
				)
			),
			'remote_data_blocks_cache'
		);

		$this->handler_stack->push( Middleware::log(
			LoggerManager::instance(),
			new MessageFormatter( '{total_time} {code} {phrase} {method} {url}' )
		) );

		$this->client = new Client( array_merge( $this->default_options, $this->options, [
			'base_uri' => $this->base_uri,
			'handler'  => $this->handler_stack,
		] ) );
	}

	/**
	 * Determine if the request request be retried.
	 *
	 * @param int               $retries Number of retries that have been attempted so far.
	 * @param RequestInterface  $request Request that was sent.
	 * @param ResponseInterface $response Response that was received.
	 * @param Exception         $exception Exception that was received (if any).
	 * @return bool Whether the request should be retried.
	 */
	public static function retry_decider( int $retries, RequestInterface $request, ?ResponseInterface $response = null, ?Exception $exception = null ): bool {
		if ( $retries >= self::MAX_RETRIES ) {
			return false;
		}

		if ( $response && $response->getStatusCode() < 500 ) {
			return false;
		}

		if ( $response && $response->getStatusCode() >= 500 ) {
			return true;
		}

		if ( $exception ) {
			$retry_on_exception = $exception instanceof ConnectException;
			return apply_filters( 'remote_data_blocks_http_client_retry_on_exception', $retry_on_exception, $retries, $request, $response, $exception );
		}

		return apply_filters( 'remote_data_blocks_http_client_retry_decider', false, $retries, $request, $response );
	}

	/**
	 * Calculate the delay before retrying a request.
	 *
	 * @param int               $retries Number of retries that have been attempted so far.
	 * @param ResponseInterface $response Response that was received.
	 * @return int Number of milliseconds to delay.
	 */
	public static function retry_delay( int $retries, ?ResponseInterface $response ): int {
		if ( ! $response instanceof ResponseInterface || ! $response->hasHeader( 'Retry-After' ) ) {
			$retry_after_ms = 1000 * $retries;
			return apply_filters( 'remote_data_blocks_http_client_retry_delay', $retry_after_ms, $retries, $response );
		}

		$retry_after = $response->getHeaderLine( 'Retry-After' );

		if ( ! is_numeric( $retry_after ) ) {
			$retry_after = ( new \DateTime( $retry_after ) )->getTimestamp() - time();
		}

		$retry_after_ms = (int) $retry_after * 1000;
		return apply_filters( 'remote_data_blocks_http_client_retry_delay', $retry_after_ms, $retries, $response );
	}

	/**
	 * Queue a request for later execution.
	 *
	 * @param string $method
	 * @param string|UriInterface $uri
	 * @param array  $options
	 */
	public function queue_request( string $method, string|UriInterface $uri, array $options = [] ) {
		$this->queued_requests[] = [
			'method'  => $method,
			'uri'     => $uri,
			'options' => array_merge( $this->options, $options ),
		];
	}

	/**
	 * Execute all queued requests in parallel.
	 */
	public function execute_parallel(): array {
		$promises = [];
		foreach ( $this->queued_requests as $request ) {
			$promises[] = $this->client->requestAsync(
				$request['method'],
				$request['uri'],
				$request['options']
			);
		}

		$results = Utils::settle( $promises )->wait();

		// Clear the queue after execution
		$this->queued_requests = [];

		return $results;
	}

	/**
	 * @param string $method
	 * @param string|UriInterface $uri
	 * @param array  $options
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function request( string $method, string|UriInterface $uri, array $options = [] ): ResponseInterface {
		return $this->client->request( $method, $uri, array_merge( $this->options, $options ) );
	}

	/**
	 * @param string $method
	 * @param string|UriInterface $uri
	 * @param array  $options
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function get( string|UriInterface $uri, array $options = [] ): ResponseInterface {
		return $this->request( 'GET', $uri, $options );
	}

	/**
	 * @param string $method
	 * @param string|UriInterface $uri
	 * @param array  $options
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function post( string|UriInterface $uri, array $options = [] ): ResponseInterface {
		return $this->request( 'POST', $uri, $options );
	}
}
