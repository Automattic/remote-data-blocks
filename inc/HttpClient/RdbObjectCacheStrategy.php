<?php declare(strict_types = 1);

namespace RemoteDataBlocks\HttpClient;

use Kevinrob\GuzzleCache\CacheEntry;
use Kevinrob\GuzzleCache\Storage\CacheStorageInterface;
use Kevinrob\GuzzleCache\Storage\WordPressObjectCacheStorage;
use Kevinrob\GuzzleCache\Strategy\CacheStrategyInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Cache strategy that caches responses in the WordPress object cache. It is
 * also backed by a runtime / in-memory cache strategy to prevent repeated
 * round-trips to the object cache. This is useful because block bindings are
 * repeated for each block and result in multiple executions of the same query.
 */
class RdbObjectCacheStrategy extends RdbCacheStrategy {
	private const WP_OBJECT_CACHE_GROUP = 'remote-data-blocks';

	private static RdbRuntimeCacheStrategy $runtime_cache_strategy;

	public function __construct( ?int $default_ttl = null, ?CacheStorageInterface $storage = null, ?CacheStrategyInterface $runtime_cache_strategy = null ) {
		parent::__construct( $default_ttl, $storage );

		// We need a single instance of the runtime / in-memory cache storage that
		// will be shared across all HttpClient instances. However, we allow it to be
		// injected for testing.
		self::$runtime_cache_strategy = $runtime_cache_strategy ?? self::$runtime_cache_strategy ?? new RdbRuntimeCacheStrategy();
	}

	protected function get_default_cache_storage(): WordPressObjectCacheStorage {
		return new WordPressObjectCacheStorage( self::WP_OBJECT_CACHE_GROUP );
	}

	protected function get_cache_type(): string {
		return 'object';
	}

	public function cache( RequestInterface $request, ResponseInterface $response ): bool {
		self::$runtime_cache_strategy->cache( $request, $response );
		return parent::cache( $request, $response );
	}

	public function delete( RequestInterface $request ): bool {
		self::$runtime_cache_strategy->delete( $request );
		return parent::delete( $request );
	}

	public function fetch( RequestInterface $request ): CacheEntry|null {
		// First check the runtime / in-memory cache.
		$result = self::$runtime_cache_strategy->fetch( $request );

		if ( null !== $result ) {
			return $result;
		}

		// Fall back to the object cache.
		$result = parent::fetch( $request );

		// Update the runtime / in-memory cache for the next fetch.
		if ( null !== $result ) {
			self::$runtime_cache_strategy->cache( $request, $result->getResponse() );
		}

		return $result;
	}

	public function update( RequestInterface $request, ResponseInterface $response ): bool {
		self::$runtime_cache_strategy->update( $request, $response );
		return parent::update( $request, $response );
	}
}
