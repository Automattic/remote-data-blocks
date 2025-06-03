<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\ShopifyMockStore;

use RemoteDataBlocks\Integrations\Shopify\ShopifyDataSource;
use RemoteDataBlocks\Integrations\Shopify\ShopifyIntegration;

/**
 * Registers a remote data block representing a product from Shopify's Mock Shop.
 * This task can be completed in the plugin settings screen without writing code,
 * but this template shows how to register it programmatically.
 *
 * @see https://mock.shop/
 */
function register_shopify_mock_store_blocks(): void {
	$shopify_data_source = ShopifyDataSource::from_array( [
		'service_config' => [
			'__version' => 1,
			'access_token' => '', // No access token needed for the mock store.
			'display_name' => 'Shopify Mock Store',
			'store_name' => 'mock.shop',
		],
	] );

	ShopifyIntegration::register_blocks_for_shopify_data_source( $shopify_data_source );
}
add_action( 'init', __NAMESPACE__ . '\\register_shopify_mock_store_blocks' );
