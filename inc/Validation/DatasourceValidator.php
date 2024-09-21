<?php

namespace RemoteDataBlocks\Validation;

use RemoteDataBlocks\Config\Datasource\DatasourceInterface;
use WP_Error;

use const RemoteDataBlocks\REMOTE_DATA_BLOCKS__DATASOURCE_CLASSMAP;

/**
 * Datasource validator class.
 * 
 * Merges the datasource base schema with the specific service schema.
 */
class DatasourceValidator extends Validator {
	/**
	 * Create a new DatasourceValidator from a service.
	 */
	public static function from_service( string $service ): DatasourceValidator|WP_Error {
		$datasource_class = REMOTE_DATA_BLOCKS__DATASOURCE_CLASSMAP[ $service ];

		if ( ! in_array( $service, REMOTE_DATA_BLOCKS__SERVICES ) || ! class_exists( $datasource_class ) ) {
			return new WP_Error( 'unsupported_data_source', __( 'Unsupported data source.', 'remote-data-blocks' ) );
		}

		return new DatasourceValidator( array_merge( DatasourceInterface::BASE_SCHEMA, $datasource_class::get_config_schema() ) );
	}
}
