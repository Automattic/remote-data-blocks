<?php

namespace RemoteDataBlocks\Sanitization;

/**
 * Sanitizer class.
 */
class Sanitizer implements SanitizerInterface {
	private array $schema;

	/**
	 * @inheritDoc
	 */
	public function __construct( array $schema ) {
		$this->schema = $schema;
	}

	/**
	 * @inheritDoc
	 */
	public function sanitize( array $data ): array {
		if ( ! isset( $this->schema['type'] ) || 'object' !== $this->schema['type'] || ! isset( $this->schema['properties'] ) ) {
			return [];
		}

		return $this->sanitize_config( $data, $this->schema['properties'] );
	}

	/**
	 * Sanitize the config, recursively if necessary, according to the schema.
	 *
	 * @param array $config The config to sanitize.
	 * @param array $schema The schema to use for sanitization.
	 * @return array The sanitized config.
	 */
	private function sanitize_config( array $config, array $schema ): array {
		$sanitized = [];

		foreach ( $schema as $key => $field_schema ) {
			if ( ! isset( $config[ $key ] ) ) {
				continue;
			}

			$value = $config[ $key ];

			if ( isset( $field_schema['sanitize'] ) ) {
				$sanitized[ $key ] = call_user_func( $field_schema['sanitize'], $value );
				continue;
			}

			switch ( $field_schema['type'] ) {
				case 'string':
					$sanitized[ $key ] = sanitize_text_field( $value );
					break;
				case 'integer':
					$sanitized[ $key ] = intval( $value );
					break;
				case 'boolean':
					$sanitized[ $key ] = (bool) $value;
					break;
				case 'array':
					if ( is_array( $value ) ) {
						if ( isset( $field_schema['items'] ) ) {
							$sanitized[ $key ] = array_map(function ( $item ) use ( $field_schema ) {
								return $this->sanitize_config( $item, $field_schema['items'] );
							}, $value);
						} else {
							$sanitized[ $key ] = array_map( 'sanitize_text_field', $value );
						}
					}
					break;
				case 'object':
					if ( is_array( $value ) && isset( $field_schema['properties'] ) ) {
						$sanitized[ $key ] = $this->sanitize_config( $value, $field_schema['properties'] );
					}
					break;
			}
		}

		return $sanitized;
	}
}
