<?php

namespace RemoteDataBlocks\Config\Datasource;

use RemoteDataBlocks\Config\ArraySerializableInterface;
use RemoteDataBlocks\Sanitization\Sanitizer;
use RemoteDataBlocks\Sanitization\SanitizerInterface;
use RemoteDataBlocks\Validation\DatasourceValidator;
use RemoteDataBlocks\Validation\ValidatorInterface;
use WP_Error;

use const RemoteDataBlocks\REMOTE_DATA_BLOCKS__DATASOURCE_CLASSMAP;

/**
 * HttpDatasource class
 *
 * Implements the HttpDatasourceInterface to define a generic HTTP datasource.
 *
 * @package remote-data-blocks
 * @since 0.1.0
 */
abstract class HttpDatasource implements DatasourceInterface, HttpDatasourceInterface, ArraySerializableInterface {
	final private function __construct( protected array $config ) {}

	/**
	 * @inheritDoc
	 */
	abstract public function get_display_name(): string;

	/**
	 * @inheritDoc
	 */
	abstract public function get_endpoint(): string;

	/**
	 * @inheritDoc
	 */
	abstract public function get_request_headers(): array;

	/**
	 * @inheritDoc
	 */
	public function get_image_url(): ?string {
		return null;
	}

	/**
	 * @inheritDoc
	 */
	abstract public static function get_config_schema(): array;

	/**
	 * @inheritDoc
	 */
	public static function from_array( array $config, ?ValidatorInterface $validator = null, ?SanitizerInterface $sanitizer = null ): DatasourceInterface|WP_Error {
		if ( ! is_string( $config['service'] ) ) {
			return new WP_Error( 'invalid_config', 'Invalid config' );
		}

		$validator = $validator ?? DatasourceValidator::from_service( $config['service'] );

		if ( is_wp_error( $validator ) ) {
			return $validator;
		}

		$validated = $validator->validate( $config );

		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		$datasource_class = REMOTE_DATA_BLOCKS__DATASOURCE_CLASSMAP[ $config['service'] ];

		if ( ! class_exists( $datasource_class ) ) {
			return new WP_Error( 'invalid_datasource', 'Invalid datasource' );
		}

		$sanitizer = $sanitizer ?? new Sanitizer( $datasource_class::get_config_schema() );
		$sanitized = $sanitizer->sanitize( $config );

		return new $datasource_class( $sanitized );
	}

	/**
	 * @inheritDoc
	 */
	public function to_array(): array {
		return $this->config;
	}
}
