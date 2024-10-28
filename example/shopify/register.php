<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\Shopify;

use RemoteDataBlocks\Integrations\Shopify\Queries\ShopifyGetProductQuery;
use RemoteDataBlocks\Integrations\Shopify\Queries\ShopifySearchProductsQuery;
use RemoteDataBlocks\Integrations\Shopify\ShopifyDataSource;
use RemoteDataBlocks\Logging\LoggerManager;
use function RemoteDataBlocks\Example\get_access_token;

function register_shopify_block(): void {
	$block_name   = 'Shopify Example';
	$access_token = get_access_token( 'shopify' );
	$store_name   = 'stoph-test';

	if ( empty( $access_token ) ) {
		$logger = LoggerManager::instance();
		$logger->warning( sprintf( '%s is not defined, cannot register %s block', 'EXAMPLE_SHOPIFY_ACCESS_TOKEN', $block_name ) );
		return;
	}

	$shopify_data_source           = ShopifyDataSource::create( $access_token, $store_name );
	$shopify_search_products_query = new ShopifySearchProductsQuery( $shopify_data_source );
	$shopify_get_product_query     = new ShopifyGetProductQuery( $shopify_data_source );

	register_remote_data_block( $block_name, $shopify_get_product_query );
	register_remote_data_search_query( $block_name, $shopify_search_products_query );
}
add_action( 'init', __NAMESPACE__ . '\\register_shopify_block' );
