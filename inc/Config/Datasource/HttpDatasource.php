<?php

namespace RemoteDataBlocks\Config\Datasource;

use RemoteDataBlocks\Config\ArraySerializableInterface;
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
	protected $config;

	private function __construct( array $config ) {
		$config_schema = static::get_config_schema();

		foreach ( $config as $key => $value ) {
			if ( isset( $config_schema[ $key ] ) && array_key_exists( 'sanitize', $config_schema[ $key ] ) ) {
				$config[ $key ] = call_user_func( $config_schema[ $key ]['sanitize'], $value );
			}
		}

		$this->config = $config;
	}

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
	public static function from_array( array $config, ?ValidatorInterface $validator = null ): DatasourceInterface|WP_Error {
		if ( ! is_string( $config['service'] ) ) {
			return new WP_Error( 'invalid_config', 'Invalid config', [ 'status' => 400 ] );
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
			return new WP_Error( 'invalid_datasource', 'Invalid datasource', [ 'status' => 400 ] );
		}

		return new $datasource_class( $config );
	}

	/**
	 * @inheritDoc
	 */
	public function to_array(): array {
		return $this->config;
	}
}
