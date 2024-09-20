<?php

namespace RemoteDataBlocks\Config\Datasource;

use RemoteDataBlocks\Config\ConfigSerializableInterface;
use RemoteDataBlocks\Validation\DatasourceValidator;
use RemoteDataBlocks\Validation\ValidatorInterface;
use WP_Error;

/**
 * HttpDatasource class
 *
 * Implements the HttpDatasourceInterface to define a generic HTTP datasource.
 *
 * @package remote-data-blocks
 * @since 0.1.0
 */
abstract class HttpDatasource implements DatasourceInterface, HttpDatasourceInterface, ConfigSerializableInterface {
	protected $config_schema = [];

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
	abstract static public function get_config_schema(): array;

	/**
	 * @inheritDoc
	 */
	public static function from_array( array $config, ?ValidatorInterface $validator = null ): static|WP_Error {
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

		return new static( $config) );
	}

	/**
	 * @inheritDoc
	 */
	abstract public function to_array(): array;
}
