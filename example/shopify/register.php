<?php

namespace RemoteDataBlocks\Example\Shopify;

use RemoteDataBlocks\Editor\ConfigurationLoader;
use RemoteDataBlocks\Logging\LoggerManager;
use function add_action;

require_once __DIR__ . '/inc/interactivity-store/interactivity-store.php';
require_once __DIR__ . '/inc/queries/class-shopify-datasource.php';
require_once __DIR__ . '/inc/queries/class-shopify-add-to-cart-mutation.php';
require_once __DIR__ . '/inc/queries/class-shopify-create-cart-mutation.php';
require_once __DIR__ . '/inc/queries/class-shopify-get-product-query.php';
require_once __DIR__ . '/inc/queries/class-shopify-remove-from-cart-mutation.php';
require_once __DIR__ . '/inc/queries/class-shopify-search-products-query.php';

function register_shopify_block() {
	$block_name   = 'Shopify Product';
	$access_token = \RemoteDataBlocks\Example\get_access_token( 'shopify' );

	if ( empty( $access_token ) ) {
		$logger = LoggerManager::instance();
		$logger->warning( sprintf( '%s is not defined, cannot register %s block', 'EXAMPLE_SHOPIFY_ACCESS_TOKEN', $block_name ) );
		return;
	}

	$shopify_datasource            = new ShopifyDatasource( $access_token );
	$shopify_search_products_query = new ShopifySearchProductsQuery( $shopify_datasource );
	$shopify_get_product_query     = new ShopifyGetProductQuery( $shopify_datasource );

	ConfigurationLoader::register_block( $block_name, $shopify_get_product_query );
	ConfigurationLoader::register_search_panel( 'Shopify Product', 'Search products', $shopify_search_products_query );

	ConfigurationLoader::register_query( $block_name, '__CREATE_CART__', new ShopifyCreateCartMutation( $shopify_datasource ) );
	ConfigurationLoader::register_query( $block_name, '__ADD_TO_CART__', new ShopifyAddToCartMutation( $shopify_datasource ) );
	ConfigurationLoader::register_query( $block_name, '__REMOVE_FROM_CART__', new ShopifyRemoveFromCartMutation( $shopify_datasource ) );

	$block_pattern = file_get_contents( __DIR__ . '/inc/patterns/product-teaser.html' );
	ConfigurationLoader::register_block_pattern( $block_name, 'remote-data-blocks/shopify-product-teaser', $block_pattern, [ 'title' => 'Shopify Product Teaser' ] );

	register_block_type( __DIR__ . '/build/blocks/shopify-cart' );
	register_block_type( __DIR__ . '/build/blocks/shopify-cart-button' );

	$asset = require __DIR__ . '/build/filters/shopify-list.asset.php';

	wp_enqueue_script(
		'filter-list-panel-shopify',
		plugins_url( 'build/filters/shopify-list.js', __FILE__ ),
		$asset['dependencies'],
		$asset['version'],
		true
	);
	wp_localize_script(
		'filter-list-panel-shopify',
		'SHOPIFY_LIST_PANEL',
		[
			'assetPath' => plugins_url( 'assets', __FILE__ ),
		]
	);
}
add_action( 'register_remote_data_blocks', __NAMESPACE__ . '\\register_shopify_block' );
