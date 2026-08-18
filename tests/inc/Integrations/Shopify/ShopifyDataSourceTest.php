<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Integrations\Shopify;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Integrations\Shopify\ShopifyDataSource;
use WP_Error;

class ShopifyDataSourceTest extends TestCase {
	public function testStorefrontAccessTokenHeaderIsIncludedInCacheKey(): void {
		$data_source = ShopifyDataSource::from_array( [
			'service_config' => [
				'__version' => 1,
				'access_token' => 'secret',
				'display_name' => 'Shopify Store',
				'store_name' => 'example',
			],
		] );

		$this->assertNotInstanceOf( WP_Error::class, $data_source );
		$this->assertSame(
			[ 'X-Shopify-Storefront-Access-Token' ],
			$data_source->get_cache_key_request_headers()
		);
	}
}
