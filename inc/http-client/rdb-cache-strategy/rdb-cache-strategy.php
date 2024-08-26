<?php

namespace RemoteDataBlocks\HttpClient;

use GraphQL\Language\AST\OperationDefinitionNode;
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
			$document = \GraphQL\Language\Parser::parse( $query );
		} catch ( \Exception $e ) {
			return false;
		}

		/**
		 * Adapted from \GraphQL\Utils\AST::getOperationAST
		 * That function only returns a value if there is only a single operation in the document.
		 * This function is more lenient and will return true if there is at least one mutation in the document.
		 *
		 * @see https://github.com/webonyx/graphql-php/blob/7e80dc0bce7e9156a4aa6ca5ceae84337ccec660/src/Utils/AST.php#L568-L596
		 * @see https://webonyx.github.io/graphql-php/class-reference/#graphqlutilsast
		 */
		foreach ( $document->definitions->getIterator() as $node ) {
			if ( ! $node instanceof OperationDefinitionNode ) {
				continue;
			}

			if ( 'mutation' === $node->operation ) {
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
		if ( false === $result ) {
			$this->logger->debug( 'Did not cache: ' . self::getRequestString( $request ) );
			return false;
		}
		$this->logger->debug( 'Cached: ' . self::getRequestString( $request ) );
		return $result;
	}
}
