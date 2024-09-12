<?php

namespace RemoteDataBlocks\Integrations;

use RemoteDataBlocks\Config\ShopifyDatasource;
use RemoteDataBlocks\Config\ShopifyGetProductQuery;
use RemoteDataBlocks\Config\ShopifySearchProductsQuery;
use RemoteDataBlocks\Editor\ConfigurationLoader;
use RemoteDataBlocks\Logging\LoggerManager;
use RemoteDataBlocks\REST\DatasourceCRUD;

require_once __DIR__ . '/datasources/shopify-datasource.php';
require_once __DIR__ . '/queries/shopify-get-product-query.php';
require_once __DIR__ . '/queries/shopify-search-products-query.php';

class ShopifyIntegration {
	public static function init(): void {
		self::register_dynamic_data_source_blocks();
	}

	private static function register_dynamic_data_source_blocks(): void {
		$data_sources = DatasourceCRUD::get_data_sources( REMOTE_DATA_BLOCKS_SHOPIFY_SERVICE );

		foreach ( $data_sources as $config ) {
			// Transform data to our experimental format, which is all array based
			$config = array_map(
				function ( $value ) {
					return is_object( $value ) ? (array) $value : $value;
				},
				(array) $config
			);
			self::register_blocks_for_shopify_data_source( $config );
		}
	}

	private static function register_blocks_for_shopify_data_source( array $config ): void {
		$shopify_datasource            = new ShopifyDatasource( $config['token'], $config['store'] );
		$shopify_search_products_query = new ShopifySearchProductsQuery( $shopify_datasource );
		$shopify_get_product_query     = new ShopifyGetProductQuery( $shopify_datasource );

		$block_name    = $shopify_datasource->get_display_name();
		$block_pattern = file_get_contents( __DIR__ . '/patterns/product-teaser.html' );

		ConfigurationLoader::register_block( $block_name, $shopify_get_product_query );
		ConfigurationLoader::register_search_query( $block_name, $shopify_search_products_query );
		ConfigurationLoader::register_block_pattern( $block_name, 'remote-data-blocks/shopify-product-teaser', $block_pattern, [ 'title' => 'Shopify Product Teaser' ] );

		LoggerManager::instance()->info( 'Registered Shopify block', [ 'block_name' => $block_name ] );
	}
}
