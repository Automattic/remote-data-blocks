<?php declare(strict_types = 1);

namespace RemoteDataBlocks\HttpClient;

use Psr\Http\Message\RequestInterface;

class RdbCacheMiddleware extends \Kevinrob\GuzzleCache\CacheMiddleware {
	public const CACHE_KEY_REQUEST_HEADERS_HEADER = 'X-Remote-Data-Blocks-Cache-Key-Headers';
	public const CACHE_KEY_REQUEST_HEADERS_OPTION = 'remote_data_blocks_cache_key_request_headers';

	public function __invoke( callable $handler ): callable {
		$handler_without_cache_metadata = function ( RequestInterface $request, array $options ) use ( $handler ) {
			return $handler( $request->withoutHeader( self::CACHE_KEY_REQUEST_HEADERS_HEADER ), $options );
		};
		$cache_handler = parent::__invoke( $handler_without_cache_metadata );

		return function ( RequestInterface $request, array $options ) use ( $cache_handler ) {
			$cache_key_request_headers = $options[ self::CACHE_KEY_REQUEST_HEADERS_OPTION ] ?? [];
			unset( $options[ self::CACHE_KEY_REQUEST_HEADERS_OPTION ] );

			if ( is_array( $cache_key_request_headers ) ) {
				$cache_key_request_headers = array_values( array_filter( $cache_key_request_headers, 'is_string' ) );
				$request = $request->withHeader( self::CACHE_KEY_REQUEST_HEADERS_HEADER, $cache_key_request_headers );
			}

			return $cache_handler( $request, $options );
		};
	}

	/**
	 * @var array<string, true>
	 */
	// phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase, SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
	protected $httpMethods = [
		'GET' => true,
		'POST' => true,
	];
}
