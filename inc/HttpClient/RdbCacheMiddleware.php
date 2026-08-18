<?php declare(strict_types = 1);

namespace RemoteDataBlocks\HttpClient;

use Psr\Http\Message\RequestInterface;

class RdbCacheMiddleware extends \Kevinrob\GuzzleCache\CacheMiddleware {
	public function __invoke( callable $handler ): callable {
		$handler_without_cache_metadata = function ( RequestInterface $request, array $options ) use ( $handler ) {
			return $handler( $request->withoutHeader( RdbCacheStrategy::CACHE_KEY_REQUEST_HEADERS_REQUEST_HEADER ), $options );
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
