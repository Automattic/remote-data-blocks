<?php

namespace RemoteDataBlocks\HttpClient;

class RdbCacheMiddleware extends \Kevinrob\GuzzleCache\CacheMiddleware {
	// phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
	protected $httpMethods = [
		'GET'  => true,
		'POST' => true,
	];
}
