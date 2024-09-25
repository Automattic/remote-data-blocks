<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\Shopify;

use RemoteDataBlocks\Integrations\Shopify\Queries\ShopifyGetProductQuery;
use RemoteDataBlocks\Integrations\Shopify\Queries\ShopifySearchProductsQuery;
use RemoteDataBlocks\Logging\LoggerManager;
use RemoteDataBlocks\WpdbStorage\DatasourceCrud;
use function register_remote_data_block;
use function register_remote_data_block_pattern;
use function register_remote_data_search_query;

class ShopifyIntegration {
	public static function init(): void {
		$data_sources = DatasourceCrud::get_data_sources( REMOTE_DATA_BLOCKS_SHOPIFY_SERVICE );

		foreach ( $data_sources as $config ) {
			self::register_blocks_for_shopify_data_source( $config );
		}
	}

	private static function register_blocks_for_shopify_data_source( array $config ): void {
		$shopify_datasource            = ShopifyDatasource::from_array( $config );
		$shopify_search_products_query = new ShopifySearchProductsQuery( $shopify_datasource );
		$shopify_get_product_query     = new ShopifyGetProductQuery( $shopify_datasource );

		$block_name    = $shopify_datasource->get_display_name();
		$block_pattern = file_get_contents( __DIR__ . '/Patterns/product-teaser.html' );

		register_remote_data_block( $block_name, $shopify_get_product_query );
		register_remote_data_search_query( $block_name, $shopify_search_products_query );
		register_remote_data_block_pattern( $block_name, 'Shopify Product Teaser', $block_pattern );

		LoggerManager::instance()->info( 'Registered Shopify block', [ 'block_name' => $block_name ] );
	}
}
