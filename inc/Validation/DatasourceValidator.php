<?php

namespace RemoteDataBlocks\Validation;

use WP_Error;

/**
 * Datasource validator class.
 * 
 * Merges the datasource base schema with the specific service schema.
 */
class DatasourceValidator extends Validator {
	private const BASE_SCHEMA = [
		'uuid'    => [
			'path'     => '$.uuid',
			'required' => true,
			'type'     => 'string',
			'callback' => 'wp_is_uuid',
		],
		'service' => [
			'path'     => '$.service',
			'required' => true,
			'type'     => 'string',
			'enum'     => REMOTE_DATA_BLOCKS__SERVICES,
		],
		'slug'    => [
			'path'     => '$.slug',
			'required' => true,
			'type'     => 'string',
			'pattern'  => '/^[a-z0-9-]+$/',
			'sanitize' => 'sanitize_text_field',
		],
	];

	/**
	 * @inheritDoc
	 */
	public function __construct( array $service_schema ) {
		$schema = array_merge( self::BASE_SCHEMA, $service_schema );
		parent::__construct( $schema );
	}

	/**
	 * Create a new DatasourceValidator from a service.
	 * 
	 * @param string $service
	 * @return DatasourceValidator
	 * @throws WP_Error if the service is unsupported.
	 */
	public static function from_service( string $service ): DatasourceValidator|WP_Error {
		$datasource_class = 'RemoteDataBlocks\\Integrations\\' . $service . '\\' . $service . 'Datasource';

		if ( ! in_array( $service, REMOTE_DATA_BLOCKS__SERVICES ) || ! class_exists( $datasource_class ) ) {
			return new WP_Error( 'unsupported_data_source', __( 'Unsupported data source.', 'remote-data-blocks' ) );
		}

		return new DatasourceValidator( $datasource_class::get_config_schema() );
	}
}
