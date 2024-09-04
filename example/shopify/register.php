<?php

namespace RemoteDataBlocks\Example\Shopify;

use RemoteDataBlocks\Config\HttpDatasource;
use RemoteDataBlocks\Config\ShopifyGetProductQuery;
use RemoteDataBlocks\Config\ShopifySearchProductsQuery;
use RemoteDataBlocks\Editor\ConfigurationLoader;
use RemoteDataBlocks\Logging\LoggerManager;
use RemoteDataBlocks\REST\DatasourceCRUD;
use function add_action;

require_once __DIR__ . '/inc/interactivity-store/interactivity-store.php';
require_once __DIR__ . '/inc/queries/class-shopify-add-to-cart-mutation.php';
require_once __DIR__ . '/inc/queries/class-shopify-create-cart-mutation.php';
require_once __DIR__ . '/inc/queries/class-shopify-get-product-query.php';
require_once __DIR__ . '/inc/queries/class-shopify-remove-from-cart-mutation.php';
require_once __DIR__ . '/inc/queries/class-shopify-search-products-query.php';

function register_shopify_block() {
	$block_name   = 'hardcode example';
	$access_token = \RemoteDataBlocks\Example\get_access_token( 'shopify' );

	if ( empty( $access_token ) ) {
		$logger = LoggerManager::instance();
		$logger->warning( sprintf( '%s is not defined, cannot register %s block', 'EXAMPLE_SHOPIFY_ACCESS_TOKEN', $block_name ) );
		return;
	}

	$config = [
		'friendly_name' => $block_name,
		'uid' => 'stoph-test',
		'endpoint' => 'https://stoph-test.myshopify.com/api/2024-04/graphql.json',
		'request_headers' => [
			'Content-Type'                      => 'application/json',
			'X-Shopify-Storefront-Access-Token' => $access_token,
		],
		'image_url' => plugins_url( '../../assets/shopify_logo_black.png', __FILE__ ),
	];

	$block_pattern = file_get_contents( __DIR__ . '/inc/patterns/product-teaser.html' );

	register_blocks_for_shopify_data_source( $config, $block_pattern );

	register_block_type( __DIR__ . '/build/blocks/shopify-cart' );
	register_block_type( __DIR__ . '/build/blocks/shopify-cart-button' );

	// Register blocks for dynamic data sources
	// @TODO: These core example integrations should probably be moved into the parent plugin.
	foreach ( DatasourceCRUD::get_data_sources( REMOTE_DATA_BLOCKS_SHOPIFY_SERVICE ) as $config ) {
		// quick hack to transform data to our experimental format, which is all array based
		$config = array_map(
			function ( $value ) {
				return is_object( $value ) ? (array) $value : $value;
			},
			(array) $config
		);
		register_blocks_for_shopify_data_source( $config, $block_pattern );
	}
}
add_action( 'register_remote_data_blocks', __NAMESPACE__ . '\\register_shopify_block' );



function register_blocks_for_shopify_data_source( array $config, string $block_pattern ): void {
	$shopify_datasource            = new HttpDatasource( $config );
	$shopify_search_products_query = new ShopifySearchProductsQuery( $shopify_datasource );
	$shopify_get_product_query     = new ShopifyGetProductQuery( $shopify_datasource );

	$block_name = 'Shopify (' . $shopify_datasource->get_friendly_name() . ')';

	ConfigurationLoader::register_block( $block_name, $shopify_get_product_query );
	ConfigurationLoader::register_search_query( $block_name, $shopify_search_products_query );

	ConfigurationLoader::register_query( $block_name, new ShopifyCreateCartMutation( $shopify_datasource ) );
	ConfigurationLoader::register_query( $block_name, new ShopifyAddToCartMutation( $shopify_datasource ) );
	ConfigurationLoader::register_query( $block_name, new ShopifyRemoveFromCartMutation( $shopify_datasource ) );

	ConfigurationLoader::register_block_pattern( $block_name, 'remote-data-blocks/shopify-product-teaser', $block_pattern, [ 'title' => 'Shopify Product Teaser' ] );
}