<?php

namespace RemoteDataBlocks\Config\Datasource;

use RemoteDataBlocks\Config\ArraySerializableInterface;
use RemoteDataBlocks\Config\UiDisplayableInterface;
use RemoteDataBlocks\Sanitization\Sanitizer;
use RemoteDataBlocks\Sanitization\SanitizerInterface;
use RemoteDataBlocks\Validation\Validator;
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
abstract class HttpDatasource implements DatasourceInterface, HttpDatasourceInterface, ArraySerializableInterface, UiDisplayableInterface {
	protected const SERVICE_NAME   = 'unknown';
	protected const SERVICE_SCHEMA = [];

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

	public function get_slug(): string {
		return $this->config['slug'];
	}
	
	/**
	 * @inheritDoc
	 */
	final public static function get_config_schema(): array {
		$schema = DatasourceInterface::BASE_SCHEMA;

		if ( isset( static::SERVICE_SCHEMA['properties'] ) ) {
			$schema['properties'] = array_merge( DatasourceInterface::BASE_SCHEMA['properties'], static::SERVICE_SCHEMA['properties'] );
		}

		return $schema;
	}

	/**
	 * @inheritDoc
	 */
	final public static function from_array( array $config, ?ValidatorInterface $validator = null, ?SanitizerInterface $sanitizer = null ): DatasourceInterface|WP_Error {
		$schema = static::get_config_schema();

		$validator = $validator ?? new Validator( $schema );
		$validated = $validator->validate( $config );

		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		$sanitizer = $sanitizer ?? new Sanitizer( $schema );
		$sanitized = $sanitizer->sanitize( $config );

		return new static( $sanitized );
	}

	/**
	 * @inheritDoc
	 */
	public function to_array(): array {
		return $this->config;
	}

	/**
	 * @inheritDoc
	 */
	public function to_ui_display(): array {
		// TODO: Implement remove from children and implement here in standardized way
		return [
			'display_name' => $this->get_display_name(),
			'slug'         => $this->get_display_name(),
			'service'      => static::SERVICE_NAME,
		];
	}
}
