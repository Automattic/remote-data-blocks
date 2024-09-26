<?php declare(strict_types = 1);

namespace RemoteDataBlocks\HttpClient;

class RdbCacheMiddleware extends \Kevinrob\GuzzleCache\CacheMiddleware {
	/**
	 * @var array<string, true>
	 *
	 * @psalm-suppress NonInvariantPropertyType reason: we are right per Kevinrob's comment in parent
	 */
	// phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase, SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
	protected $httpMethods = [
		'GET'  => true,
		'POST' => true,
	];
}
