<?php declare(strict_types = 1);

namespace RemoteDataBlocks\HttpClient;

class RdbCacheMiddleware extends \Kevinrob\GuzzleCache\CacheMiddleware {
	/**
	 * @var array<string, bool>
	 */
	// phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
	protected array $httpMethods = [
		'GET'  => true,
		'POST' => true,
	];
}
