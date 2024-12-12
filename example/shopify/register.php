<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\Shopify;

use RemoteDataBlocks\Integrations\Shopify\ShopifyDataSource;
use RemoteDataBlocks\Integrations\Shopify\ShopifyIntegration;
use function RemoteDataBlocks\Example\get_access_token;

function register_shopify_block(): void {
	$access_token = get_access_token( 'shopify' );

	if ( empty( $access_token ) ) {
		return;
	}

	$shopify_data_source = ShopifyDataSource::from_array( [
		'service_config' => [
			'__version' => 1,
			'access_token' => $access_token,
			'display_name' => 'Shopify Example',
			'store_name' => 'stoph-test',
		],
	] );

	ShopifyIntegration::register_blocks_for_shopify_data_source( $shopify_data_source );
}
add_action( 'init', __NAMESPACE__ . '\\register_shopify_block' );
