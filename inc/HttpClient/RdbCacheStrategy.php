<?php declare(strict_types = 1);

namespace RemoteDataBlocks\HttpClient;

use DateTime;
use Kevinrob\GuzzleCache\CacheEntry;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\KeyValueHttpHeader;
use Kevinrob\GuzzleCache\Storage\CacheStorageInterface;
use Kevinrob\GuzzleCache\Storage\WordPressObjectCacheStorage;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use function wp_rand;

class RdbCacheStrategy extends GreedyCacheStrategy {
	public const CACHE_AGE_RESPONSE_HEADER = 'Age';
	public const CACHE_STATUS_RESPONSE_HEADER = CacheMiddleware::HEADER_CACHE_INFO;
	public const CACHE_TTL_REQUEST_HEADER = GreedyCacheStrategy::HEADER_TTL;
	public const CACHE_KEY_REQUEST_HEADERS_REQUEST_HEADER = 'X-Remote-Data-Blocks-Cache-Key-Headers';
	public const WP_OBJECT_CACHE_GROUP = 'remote-data-blocks';

	private const ERROR_CACHE_TTL_IN_SECONDS = 30; // 30 seconds for error responses
	private const FALLBACK_CACHE_TTL_IN_SECONDS = 300; // 5 minutes for success responses

	public function __construct( ?CacheStorageInterface $storage = null ) {
		parent::__construct(
			$storage ?? new WordPressObjectCacheStorage( self::WP_OBJECT_CACHE_GROUP ),
			self::FALLBACK_CACHE_TTL_IN_SECONDS
		);
	}

	public static function get_object_cache_key_from_request( RequestInterface $request ): string {
		$request_body = (string) $request->getBody();
		$request_method = $request->getMethod();
		$request_uri = (string) $request->getUri();

		$cache_key_request_headers = CacheKeyRequestHeaders::merge(
			$request->getHeader( self::CACHE_KEY_REQUEST_HEADERS_REQUEST_HEADER )
		);

		$cache_headers = [];
		foreach ( $cache_key_request_headers as $header ) {
			if ( $request->hasHeader( $header ) ) {
				$cache_headers[ $header ] = $request->getHeader( $header );
			}
		}

		$input_hash = md5( wp_json_encode( [
			'body' => $request_body,
			'headers' => $cache_headers,
			'method' => $request_method,
			'uri' => (string) $request_uri,
		] ) );

		return sprintf( 'http-client:%s', $input_hash );
	}

	/** @psalm-suppress ParamNameMismatch reason: parent is camelCase, but we want snake_case */
	protected function getCacheKey( RequestInterface $request, ?KeyValueHttpHeader $_vary_headers = null ): string {
		return self::get_object_cache_key_from_request( $request );
	}

	protected function getCacheObject( RequestInterface $request, ResponseInterface $response ): ?CacheEntry {
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$ttl = $this->defaultTtl;
		if ( $request->hasHeader( static::HEADER_TTL ) ) {
			$ttl_header_values = $request->getHeader( static::HEADER_TTL );
			$ttl = (int) reset( $ttl_header_values );
		}

		if ( ! array_key_exists( $response->getStatusCode(), $this->statusAccepted ) ) {
			// Cache it for a short time period to prevent error floods.
			$ttl = self::ERROR_CACHE_TTL_IN_SECONDS;
		}

		// Add a random jitter to the TTL to avoid simultaneous cache invalidation.
		// The upper bound of the jitter should be 10% of the TTL or 20 seconds,
		// whichever is smaller.
		$jitter = intval( ceil( min( $ttl * 0.1, 20 ) ) );
		$ttl = intval( $ttl ) + wp_rand( 0, $jitter );

		// Cache-key request headers are resolved per request in getCacheKey(), so
		// the parent's static vary-header check does not apply here.

		$response = $response->withoutHeader( 'Etag' )->withoutHeader( 'Last-Modified' );

		$cache_request = $request
			->withoutHeader( static::HEADER_TTL )
			->withoutHeader( self::CACHE_KEY_REQUEST_HEADERS_REQUEST_HEADER );

		return new CacheEntry( $cache_request, $response, new DateTime( sprintf( '%+d seconds', $ttl ) ) );
	}
}
