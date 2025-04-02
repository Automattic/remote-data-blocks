<?php declare(strict_types = 1);

namespace RemoteDataBlocks\HttpClient;

use Kevinrob\GuzzleCache\Storage\VolatileRuntimeStorage;
use Psr\Http\Message\RequestInterface;

class RdbRuntimeCacheStrategy extends RdbCacheStrategy {
	protected function get_default_cache_storage(): VolatileRuntimeStorage {
		return new VolatileRuntimeStorage();
	}

	protected function get_cache_type(): string {
		return 'in-memory';
	}

	/**
	 * Never bypass the runtime / in-memory cache, to ensure performant repeated
	 * query executions within the same request.
	 */
	final protected function should_bypass_cache( RequestInterface $request ): bool {
		return false;
	}
}
