<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Integrations\Shopify;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Integrations\Shopify\ShopifyDataSource;
use RemoteDataBlocks\Integrations\Shopify\ShopifyIntegration;
use WP_Error;

class ShopifyIntegrationTest extends TestCase {
	public function testQueriesIncludeStorefrontAccessTokenHeaderInCacheKey(): void {
		$data_source = ShopifyDataSource::from_array( [
			'service_config' => [
				'__version' => 1,
				'access_token' => 'secret',
				'display_name' => 'Shopify Store',
				'store_name' => 'example',
			],
		] );

		$this->assertNotInstanceOf( WP_Error::class, $data_source );
		$queries = ShopifyIntegration::get_queries( $data_source );

		foreach ( $queries as $query ) {
			$this->assertSame(
				[ 'X-Shopify-Storefront-Access-Token' ],
				$query->to_array()['cache_key_request_headers'] ?? null
			);
		}
	}
}
