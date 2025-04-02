<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\HttpClient;

use DateTime;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\HttpClient\RdbObjectCacheStrategy;
use RemoteDataBlocks\HttpClient\RdbRuntimeCacheStrategy;
use Kevinrob\GuzzleCache\CacheEntry;
use Kevinrob\GuzzleCache\Storage\VolatileRuntimeStorage;

class RdbObjectCacheStrategyTest extends TestCase {
	private VolatileRuntimeStorage $cache_storage;
	private RdbObjectCacheStrategy $cache_strategy;
	private RdbRuntimeCacheStrategy $runtime_cache_strategy_mock;

	protected function setUp(): void {
		// Mock the runtime / in-memory cache strategy so that we can assert
		// that methods were called.
		$this->runtime_cache_strategy_mock = $this->createMock( RdbRuntimeCacheStrategy::class );

		// Use runtime / in-memory cache storage instead of the WordPress
		// object cache for testing.
		$this->cache_storage = new VolatileRuntimeStorage();
		$this->cache_strategy = new RdbObjectCacheStrategy( null, $this->cache_storage, $this->runtime_cache_strategy_mock );
	}

	public function test_cache_uses_runtime_cache_strategy(): void {
		$request = new Request( 'GET', 'https://example.com' );
		$response = new Response( 200, [], 'Success' );

		$this->runtime_cache_strategy_mock
			->expects( $this->once() )
			->method( 'cache' )
			->with( $request, $response ); // expect runtime cache SET

		$this->cache_strategy->cache( $request, $response );
	}

	public function test_delete_uses_runtime_cache_strategy(): void {
		$request = new Request( 'GET', 'https://example.com' );

		$this->runtime_cache_strategy_mock
			->expects( $this->once() )
			->method( 'delete' )
			->with( $request ); // expect runtime cache DELETE

		$this->cache_strategy->delete( $request );
	}

	public function test_fetch_uses_runtime_cache_strategy(): void {
		$request = new Request( 'GET', 'https://example.com' );
		$response = new Response( 200, [ 'Age' => 0 ], 'Success' );

		$cache_entry = new CacheEntry( $request, $response, new DateTime() );

		$this->runtime_cache_strategy_mock
			->expects( $this->once() )
			->method( 'fetch' )
			->with( $request )
			->willReturn( $cache_entry ); // runtime cache HIT

		$result = $this->cache_strategy->fetch( $request );

		$this->assertSame( $cache_entry, $result );
	}

	public function test_fetch_falls_back_to_object_cache_and_caches_in_runtime(): void {
		$request = new Request( 'GET', 'https://example.com' );
		$response = new Response( 200, [ 'Age' => 0 ], 'Success' );

		// Put a cache entry in the object cache storage.
		$cache_entry = new CacheEntry( $request, $response, new DateTime() );
		$this->cache_storage->save( '0e2da42303088a0018d615be305320d416c337b2bbdc42cc6d634d3501426874', $cache_entry );

		$this->runtime_cache_strategy_mock
			->expects( $this->once() )
			->method( 'fetch' )
			->with( $request )
			->willReturn( null ); // runtime cache MISS

		$this->runtime_cache_strategy_mock
			->expects( $this->once() )
			->method( 'cache' )
			->with( $request, $response ); // expect runtime cache SET

		$result = $this->cache_strategy->fetch( $request );

		$this->assertInstanceOf( CacheEntry::class, $result );
		$this->assertSame( $cache_entry, $result ); // expect cache entry from object cache
	}

	public function test_update_uses_runtime_cache_strategy(): void {
		$request = new Request( 'GET', 'https://example.com' );
		$response = new Response( 200, [], 'Success' );

		$this->runtime_cache_strategy_mock
			->expects( $this->once() )
			->method( 'update' )
			->with( $request, $response ); // expect runtime cache UPDATE

		$this->cache_strategy->update( $request, $response );
	}
}
