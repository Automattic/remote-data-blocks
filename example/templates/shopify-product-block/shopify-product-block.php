<?php

use RemoteDataBlocks\Integrations\Shopify\ShopifyDataSource;
use RemoteDataBlocks\Integrations\Shopify\ShopifyIntegration;

/**
 * Registers a remote data block representing a product from a Shopify store.
 * This task can be completed in the plugin settings screen without writing code,
 * but this template shows how to register it programmatically.
 *
 * Replace the placeholders with your Shopify access token and store name.
 */
function register_shopify_remote_data_block(): void {
	$shopify_data_source = ShopifyDataSource::from_array( [
		'service_config' => [
			'__version' => 1,
			'access_token' => '{{ Access Token }}',
			'display_name' => '{{ Shopify Store Display Name }}',
			'store_name' => '{{ store-name.myshopify.com }}',
		],
	] );

	ShopifyIntegration::register_blocks_for_shopify_data_source( $shopify_data_source );
}
add_action( 'init', 'register_shopify_remote_data_block' );
