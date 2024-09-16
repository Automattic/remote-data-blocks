<?php

namespace RemoteDataBlocks\Integrations\Shopify;

use RemoteDataBlocks\Editor\BlockManagement\ConfigRegistry;
use RemoteDataBlocks\Integrations\Shopify\Queries\ShopifyGetProductQuery;
use RemoteDataBlocks\Integrations\Shopify\Queries\ShopifySearchProductsQuery;
use RemoteDataBlocks\Logging\LoggerManager;
use RemoteDataBlocks\WpdbStorage\DatasourceCrud;

class ShopifyIntegration {
	public static function init(): void {
		self::register_dynamic_data_source_blocks();
	}

	private static function register_dynamic_data_source_blocks(): void {
		$data_sources = DatasourceCrud::get_data_sources( REMOTE_DATA_BLOCKS_SHOPIFY_SERVICE );

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
		$block_pattern = file_get_contents( __DIR__ . '/Patterns/product-teaser.html' );

		ConfigRegistry::register_block( $block_name, $shopify_get_product_query );
		ConfigRegistry::register_search_query( $block_name, $shopify_search_products_query );
		ConfigRegistry::register_block_pattern( $block_name, 'remote-data-blocks/shopify-product-teaser', $block_pattern, [ 'title' => 'Shopify Product Teaser' ] );

		LoggerManager::instance()->info( 'Registered Shopify block', [ 'block_name' => $block_name ] );
	}
}
