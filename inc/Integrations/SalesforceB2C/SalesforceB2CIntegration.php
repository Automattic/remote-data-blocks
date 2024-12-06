<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Integrations\SalesforceB2C;

use RemoteDataBlocks\Integrations\SalesforceB2C\Queries\SalesforceB2CGetProductQuery;
use RemoteDataBlocks\Integrations\SalesforceB2C\Queries\SalesforceB2CSearchProductsQuery;
use RemoteDataBlocks\Logging\LoggerManager;
use RemoteDataBlocks\WpdbStorage\DataSourceCrud;

class SalesforceB2CIntegration {
	public static function init(): void {
		$data_sources = DataSourceCrud::get_data_sources( REMOTE_DATA_BLOCKS_SALESFORCE_B2C_SERVICE );

		foreach ( $data_sources as $config ) {
			self::register_blocks_for_salesforce_data_source( $config );
		}
	}

	private static function register_blocks_for_salesforce_data_source( array $config ): void {
		$salesforce_data_source = SalesforceB2CDataSource::from_array( $config );
		$salesforce_get_product_query = new SalesforceB2CGetProductQuery( $salesforce_data_source );
		$salesforce_search_products_query = new SalesforceB2CSearchProductsQuery( $salesforce_data_source );

		$block_name = $salesforce_data_source->get_display_name();
		register_remote_data_block( $block_name, $salesforce_get_product_query );
		register_remote_data_search_query( $block_name, $salesforce_search_products_query );

		LoggerManager::instance()->info( 'Registered Salesforce B2C block', [ 'block_name' => $block_name ] );
	}
}
