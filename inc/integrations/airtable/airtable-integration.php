<?php

namespace RemoteDataBlocks\Integrations\Shopify;

use RemoteDataBlocks\Config\AirtableDatasource;
use RemoteDataBlocks\Logging\LoggerManager;
use RemoteDataBlocks\REST\DatasourceCRUD;

class AirtableIntegration {
    public static function init(): void {
        self::register_dynamic_data_source_blocks();
    }

    private static function register_dynamic_data_source_blocks(): void {
        $data_sources = DatasourceCRUD::get_data_sources( REMOTE_DATA_BLOCKS_AIRTABLE_SERVICE );

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
        $airtable_datasource            = new AirtableDatasource();

        $block_name = 'Airtable (' . $airtable_datasource->get_display_name() . ')';

        // TODO: Implement query UI
    }
}
