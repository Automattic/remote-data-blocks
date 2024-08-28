<?php

namespace RemoteDataBlocks\HttpClient;

use Graphpinator\Parser\Parser as GraphpinatorParser;
use Kevinrob\GuzzleCache\KeyValueHttpHeader;
use Kevinrob\GuzzleCache\Storage\CacheStorageInterface;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;
use Psr\Http\Message\RequestInterface;
use RemoteDataBlocks\Logging\Logger;
use RemoteDataBlocks\Logging\LoggerManager;

class RdbCacheStrategy extends GreedyCacheStrategy {
	const MAX_INSPECTABLE_BODY_LENGTH = 50000;

	private Logger $logger;

	public function __construct( CacheStorageInterface $cache = null, $default_ttl, KeyValueHttpHeader $vary_headers = null ) {
		parent::__construct( $cache, $default_ttl, $vary_headers );
		$this->logger = LoggerManager::instance( __CLASS__ );
	}

	private static function getRequestString( RequestInterface $request ) {
		$uri        = $request->getUri();
		$uri_string = $uri->getScheme() . '://' . $uri->getHost() . $uri->getPath();
		$method     = $request->getMethod();
		$body       = $request->getBody()->getContents();
		return $method . ' ' . $uri_string . ' ' . $body;
	}

	/**
	 * Check if the string contains a GraphQL mutation
	 *
	 * @param string $query
	 * @return boolean
	 */
	private static function has_graphql_mutation( string $query ): bool {
		if ( empty( $query ) ) {
			return false;
		}

		try {
			$parsed = GraphpinatorParser::parseString( $query ); 
		} catch ( \Exception $e ) {
			return false;
		}

		$operations = $parsed->getOperations();

		foreach ( $operations as $operation ) {
			if ( $operation->getType() === 'mutation' ) {
				return true;
			}
		}

		return false;
	}

	private function should_bypass_cache( RequestInterface $request ) {
		if ( apply_filters( 'remote_data_blocks_bypass_cache', false, $request ) ) {
			return true;
		}

		$request_method = $request->getMethod();

		if ( 'POST' === strtoupper( $request_method ) ) {
			$body = $request->getBody()->getContents();

			// Check if the request contain a GraphQL mutation
			if ( ! empty( $body ) ) {
				if ( strlen( $body ) > self::MAX_INSPECTABLE_BODY_LENGTH ) {
					return true;
				}

				try {
					// TODO: Only run this against APIs we suspect contain a GraphQL body
					$decoded = json_decode( $body );
					if ( is_object( $decoded ) && self::has_graphql_mutation( $decoded->query ?? '' ) ) {
						return true;
					}
				} catch ( \Exception $e ) {
					$this->logger->debug( 'Error parsing request body: ' . $e->getMessage() );
				}
			}
		}

		return false;
	}

	protected function getCacheKey( RequestInterface $request, KeyValueHttpHeader $vary_headers = null ) {
		$cache_key = parent::getCacheKey( $request, $vary_headers );

		if ( $request->getMethod() === 'POST' ) {
			$body = $request->getBody();
			if ( empty( $body ) ) {
				return $cache_key;
			}
			$cache_key .= '-' . md5( $body );
		}

		return $cache_key;
	}

	public function fetch( RequestInterface $request ) {
		if ( $this->should_bypass_cache( $request ) ) {
			$this->logger->debug( 'Cache Bypass: ' . self::getRequestString( $request ) );
			return null;
		}

		$result = parent::fetch( $request );

		if ( null === $result ) {
			$this->logger->debug( 'Cache Miss: ' . self::getRequestString( $request ) );
			return null;
		}
		$this->logger->debug( 'Cache Hit: ' . self::getRequestString( $request ) );
		return $result;
	}

	public function cache( RequestInterface $request, $response ): bool {
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$cache_ttl = $this->defaultTtl;

		// Negative TTL indicates disabled caching.
		if ( $cache_ttl < 0 ) {
			$this->logger->debug( 'Did not cache (negative TTL): ' . self::getRequestString( $request ) );
			return false;
		}

		$result = parent::cache( $request, $response );
		if ( false === $result ) {
			$this->logger->debug( 'Did not cache (uncacheable): ' . self::getRequestString( $request ) );
			return false;
		}
		$this->logger->debug( 'Cached (TTL=' . $cache_ttl . '): ' . self::getRequestString( $request ) );
		return $result;
	}
}
