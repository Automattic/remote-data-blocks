<?php declare(strict_types = 1);

namespace RemoteDataBlocks\HttpClient;

use Psr\Http\Message\RequestInterface;

class RdbCacheMiddleware extends \Kevinrob\GuzzleCache\CacheMiddleware {
	public const CACHE_KEY_REQUEST_HEADERS_HEADER = 'X-Remote-Data-Blocks-Cache-Key-Headers';

	public function __invoke( callable $handler ): callable {
		$handler_without_cache_metadata = function ( RequestInterface $request, array $options ) use ( $handler ) {
			return $handler( $request->withoutHeader( self::CACHE_KEY_REQUEST_HEADERS_HEADER ), $options );
		};

		return parent::__invoke( $handler_without_cache_metadata );
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
