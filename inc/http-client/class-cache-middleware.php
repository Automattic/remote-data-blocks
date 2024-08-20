<?php

namespace RemoteDataBlocks\HttpClient;

use Kevinrob\GuzzleCache\CacheMiddleware as GuzzleCacheCacheMiddleware;

class CacheMiddleware extends GuzzleCacheCacheMiddleware {
	// phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
	protected $httpMethods = [
		'GET'  => true,
		'POST' => true,
	];
}
