<?php

namespace RemoteDataBlocks\HttpClient;

use Kevinrob\GuzzleCache\KeyValueHttpHeader;
use Kevinrob\GuzzleCache\Storage\CacheStorageInterface;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;
use Psr\Http\Message\RequestInterface;
use RemoteDataBlocks\Logging\Logger;
use RemoteDataBlocks\Logging\LoggerManager;

class CacheStrategy extends GreedyCacheStrategy {
	const HEADER_TTL          = 'X-RemoteDataBlocks-TTL';
	const HEADER_BYPASS_CACHE = 'X-RemoteDataBlocks-Bypass-Cache';

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
	 * Check if the request is a GraphQL mutation
	 *
	 * @param string $query
	 *
	 * @return boolean
	 * @see https://webonyx.github.io/graphql-php/class-reference/#graphqlutilsast
	 */
	private static function has_graphql_mutation( string $query ): bool {
		$parsed = \GraphQL\Language\Parser::parse( $query );
		return (bool) \GraphQL\Utils\AST::getOperationAST( $parsed, 'mutation' );
	}

	protected function should_bypass_cache( RequestInterface $request ) {
		$request_method = $request->getMethod();

		if ( in_array( $request_method, [ 'OPTIONS', 'GET' ] ) ) {
			return apply_filters( 'remote_data_blocks_bypass_cache_' . $request_method, true, $request );
		}

		$body = $request->getBody()->getContents();

		// Check if the request is a GraphQL mutation
		if ( ! empty( $body ) ) {
			// TODO: Only run this against requests we suspect contain a GraphQL body
			try {
				$decoded = json_decode( $body );
				if ( is_object( $decoded ) && $decoded->query ) {
					if ( self::has_graphql_mutation( $decoded->query ) ) {
						return true;
					}
				}
			} catch ( \Exception $e ) {
				$this->logger->debug( 'Error parsing request body: ' . $e->getMessage() );
			}
		}

		$bypass = $request->getHeader( self::HEADER_BYPASS_CACHE );
		if ( ! empty( $bypass ) ) {
			return true;
		}

		return false;
	}

	public function fetch( RequestInterface $request ) {
		if ( $this->should_bypass_cache( $request ) ) {
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

	public function cache( RequestInterface $request, $response ) {
		$result = parent::cache( $request, $response );
		$logger = LoggerManager::instance();
		if ( false === $result ) {
			$this->logger->debug( 'Did not cache: ' . self::getRequestString( $request ) );
			return false;
		}
		$this->logger->debug( 'Cached: ' . self::getRequestString( $request ) );
	}
}
