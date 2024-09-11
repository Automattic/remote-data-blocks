<?php

namespace RemoteDataBlocks\Integrations\Shopify;

use RemoteDataBlocks\Config\ShopifyDatasource;
use RemoteDataBlocks\Config\ShopifyGetProductQuery;
use RemoteDataBlocks\Config\ShopifySearchProductsQuery;
use RemoteDataBlocks\Editor\ConfigurationLoader;
use RemoteDataBlocks\Logging\LoggerManager;
use RemoteDataBlocks\REST\DatasourceCRUD;

class ShopifyIntegration {
    private string $block_pattern;

    public function __construct() {
        $this->block_pattern = file_get_contents( __DIR__ . '/inc/patterns/product-teaser.html' );
    }

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
        $shopify_datasource            = new ShopifyDatasource();
        $shopify_search_products_query = new ShopifySearchProductsQuery( $shopify_datasource );
        $shopify_get_product_query     = new ShopifyGetProductQuery( $shopify_datasource );

        $block_name = 'Shopify (' . $shopify_datasource->get_display_name() . ')';

        ConfigurationLoader::register_block( $block_name, $shopify_get_product_query );
        ConfigurationLoader::register_search_query( $block_name, $shopify_search_products_query );
        ConfigurationLoader::register_block_pattern( $block_name, 'remote-data-blocks/shopify-product-teaser', $this->block_pattern, [ 'title' => 'Shopify Product Teaser' ] );
    }
}
