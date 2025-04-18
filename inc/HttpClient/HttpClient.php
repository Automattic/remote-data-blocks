<?php declare(strict_types = 1);

namespace RemoteDataBlocks\HttpClient;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Client;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use RemoteDataBlocks\HttpClient\RdbCacheStrategy;
use RemoteDataBlocks\HttpClient\RdbCacheMiddleware;
use RemoteDataBlocks\Logging\LoggerManager;

defined( 'ABSPATH' ) || exit();

class HttpClient {
	public Client $client;

	public const CACHE_TTL_CLIENT_OPTION_KEY = '__default_cache_ttl';

	private string $base_uri;
	private HandlerStack $handler_stack;
	private static RdbCacheMiddleware $cache_middleware;

	/**
	 * @var array<string, string>
	 */
	private array $headers = [];

	/**
	 * @var array<string, mixed>
	 */
	private array $options = [];

	/**
	 * @var array<string, mixed>
	 */
	private array $default_options = [
		'headers' => [
			'User-Agent' => 'WordPress Remote Data Blocks/1.0',
		],
	];

	/**
	 * Get the cache middleware for the HTTP client.
	 */
	protected static function get_cache_middleware(): callable {
		if ( ! isset( self::$cache_middleware ) ) {
			self::$cache_middleware = new RdbCacheMiddleware( new RdbCacheStrategy() );
		}

		return self::$cache_middleware;
	}

	/**
	 * Initialize the HTTP client.
	 */
	public function init( string $base_uri, array $headers = [], array $client_options = [] ): void {
		$this->base_uri = $base_uri;
		$this->headers = $headers;
		$this->options = $client_options;

		// Initialize a request handler that uses wp_remote_request instead of cURL.
		// PHP cURL bindings are not always available, e.g., in WASM environments
		// like WP Now and WP Playground.
		$request_handler = new WPRemoteRequestHandler();

		$this->handler_stack = HandlerStack::create( $request_handler );

		$this->handler_stack->push( Middleware::mapRequest( function ( RequestInterface $request ) {
			foreach ( $this->headers as $header => $value ) {
				$request = $request->withHeader( $header, $value );
			}

			return $request;
		} ) );

		$default_ttl = $client_options[ self::CACHE_TTL_CLIENT_OPTION_KEY ] ?? null;
		$cache_middleware = self::get_cache_middleware( $default_ttl );
		$this->handler_stack->push( $cache_middleware, 'remote_data_blocks_cache' );

		$this->handler_stack->push( Middleware::log(
			LoggerManager::instance(),
			new MessageFormatter( '{total_time} {code} {phrase} {method} {url}' )
		) );

		$this->client = new Client( array_merge( $this->default_options, $this->options, [
			'base_uri' => $this->base_uri,
			'handler' => $this->handler_stack,
		] ) );
	}

	/**
	 * Execute a request.
	 */
	public function request( string $method, string|UriInterface $uri, array $options = [] ): ResponseInterface {
		return $this->client->request( $method, $uri, array_merge( $this->options, $options ) );
	}
}
