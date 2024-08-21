<?php

namespace RemoteDataBlocks\HttpClient;

use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;
use Psr\Http\Message\RequestInterface;
use RemoteDataBlocks\Logging\LoggerManager;

class CacheStrategy extends GreedyCacheStrategy {
	const HEADER_TTL          = 'X-RemoteDataBlocks-TTL';
	const HEADER_BYPASS_CACHE = 'X-RemoteDataBlocks-Bypass-Cache';

	private static function getRequestString( RequestInterface $request ) {
		$uri        = $request->getUri();
		$uri_string = $uri->getScheme() . '://' . $uri->getHost() . $uri->getPath();
		$method     = $request->getMethod();
		$body       = $request->getBody()->getContents();
		return $method . ' ' . $uri_string . ' ' . $body;
	}

	protected function should_bypass_cache( RequestInterface $request ) {
		$bypass = $request->getHeader( self::HEADER_BYPASS_CACHE );
		if ( ! empty( $bypass ) ) {
			return true;
		}

		return false;
	}

	public function fetch( RequestInterface $request ) {
		$logger = LoggerManager::instance();

		if ( $this->should_bypass_cache( $request ) ) {
			$logger->debug( 'Bypassing cache: ' . self::getRequestString( $request ) );
			return null;
		}

		$result = parent::fetch( $request );

		if ( null === $result ) {
			$logger->debug( 'Cache Miss: ' . self::getRequestString( $request ) );
			return null;
		}
		$logger->debug( 'Cache Hit: ' . self::getRequestString( $request ) );
		return $result;
	}

	public function cache( RequestInterface $request, $response ) {
		$result = parent::cache( $request, $response );
		$logger = LoggerManager::instance();
		if ( false === $result ) {
			$logger->debug( 'Did not cache: ' . self::getRequestString( $request ) );
			return false;
		}
		$logger->debug( 'Cached: ' . self::getRequestString( $request ) );
	}
}
