<?php declare(strict_types = 1);

namespace RemoteDataBlocks\HttpClient;

use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\KeyValueHttpHeader;
use Kevinrob\GuzzleCache\Storage\CacheStorageInterface;
use Kevinrob\GuzzleCache\Storage\WordPressObjectCacheStorage;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;
use Psr\Http\Message\RequestInterface;

class RdbCacheStrategy extends GreedyCacheStrategy {
	public const CACHE_AGE_RESPONSE_HEADER = 'Age';
	public const CACHE_STATUS_RESPONSE_HEADER = CacheMiddleware::HEADER_CACHE_INFO;
	public const CACHE_TTL_REQUEST_HEADER = GreedyCacheStrategy::HEADER_TTL;
	public const WP_OBJECT_CACHE_GROUP = 'remote-data-blocks';

	private const CACHE_INVALIDATING_REQUEST_HEADERS = [ 'Authorization', 'Cache-Control' ];
	private const FALLBACK_CACHE_TTL_IN_SECONDS = 300; // 5 minutes

	public function __construct( ?CacheStorageInterface $storage = null ) {
		// Filter this if customization is needed.
		$vary_headers = new KeyValueHttpHeader( self::CACHE_INVALIDATING_REQUEST_HEADERS );

		parent::__construct(
			$storage ?? new WordPressObjectCacheStorage( self::WP_OBJECT_CACHE_GROUP ),
			self::FALLBACK_CACHE_TTL_IN_SECONDS,
			$vary_headers
		);
	}

	public static function get_object_cache_key_from_request( RequestInterface $request ): string {
		$request_body = (string) $request->getBody();
		$request_headers = $request->getHeaders();
		$request_method = $request->getMethod();
		$request_uri = (string) $request->getUri();

		$cache_headers = [];
		foreach ( self::CACHE_INVALIDATING_REQUEST_HEADERS as $header ) {
			if ( isset( $request_headers[ $header ] ) ) {
				$cache_headers[ $header ] = $request_headers[ $header ];
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
}
