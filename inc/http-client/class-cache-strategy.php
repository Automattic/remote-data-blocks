<?php

namespace RemoteDataBlocks\HttpClient;

use Kevinrob\GuzzleCache\KeyValueHttpHeader;
use Kevinrob\GuzzleCache\Storage\CacheStorageInterface;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;
use RemoteDataBlocks\Logging\LoggerManager;

class CacheStrategy extends GreedyCacheStrategy {
	protected $logger;

	public function __construct( CacheStorageInterface $cache = null, $default_ttl, KeyValueHttpHeader $vary_headers = null ) {
		$this->logger = LoggerManager::instance();
		parent::__construct( $cache, $default_ttl, $vary_headers );
	}
}
