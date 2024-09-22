<?php

namespace RemoteDataBlocks\Validation;

use WP_Error;

/**
 * Validator class.
 */
class Validator implements ValidatorInterface {
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
	public function validate( array $data ): bool|WP_Error {
		return $this->validate_schema( $this->schema, $data );
	}

	private function validate_schema( array $schema, $data ): bool|WP_Error {
		if ( isset( $schema['type'] ) && ! $this->check_type( $data, $schema['type'] ) ) {
			// translators: %1$s is the expected PHP data type, %2$s is the actual PHP data type.
			return new WP_Error( 'invalid_type', sprintf( __( 'Expected %1$s, got %2$s.', 'remote-data-blocks' ), $schema['type'], gettype( $data ) ) );
		}

		if ( isset( $schema['pattern'] ) && is_string( $data ) && ! preg_match( $schema['pattern'], $data ) ) {
			// translators: %1$s is the expected regex pattern, %2$s is the actual value.
			return new WP_Error( 'invalid_format', sprintf( __( 'Expected %1$s, got %2$s.', 'remote-data-blocks' ), $schema['pattern'], $data ) );
		}

		if ( isset( $schema['enum'] ) && ! in_array( $data, $schema['enum'] ) ) {
			// translators: %1$s is the expected value, %2$s is the actual value.
			return new WP_Error( 'invalid_value', sprintf( __( 'Expected %1$s, got %2$s.', 'remote-data-blocks' ), implode( ', ', $schema['enum'] ), $data ) );
		}

		if ( isset( $schema['properties'] ) && is_array( $schema['properties'] ) ) {
			foreach ( $schema['properties'] as $field => $field_schema ) {
				if ( ! isset( $data[ $field ] ) ) {
					// translators: %1$s is the missing field name.
					return new WP_Error( 'missing_field', sprintf( __( 'Missing field %1$s.', 'remote-data-blocks' ), $field ) );
				}
				
				$result = $this->validate_schema( $field_schema, $data[ $field ] );
				if ( is_wp_error( $result ) ) {
					return $result;
				}
			}
		}

		if ( isset( $schema['items'] ) ) {
			if ( ! is_array( $data ) ) {
				// translators: %1$s is the expected PHP data type, %2$s is the actual PHP data type.
				return new WP_Error( 'invalid_array', sprintf( __( 'Expected %1$s, got %2$s.', 'remote-data-blocks' ), 'array', gettype( $data ) ) );
			}

			foreach ( $data as $item ) {
				$result = $this->validate_schema( $schema['items'], $item );
				if ( is_wp_error( $result ) ) {
					return $result;
				}
			}
		}

		return true;
	}

	private function check_type( $value, string $expected_type ): bool {
		switch ( $expected_type ) {
			case 'array':
				return is_array( $value );
			case 'object':
				return is_object( $value ) || ( is_array( $value ) && ! array_is_list( $value ) );
			case 'string':
				return is_string( $value );
			case 'number':
				return is_numeric( $value );
			case 'integer':
				return is_int( $value );
			case 'boolean':
				return is_bool( $value );
			case 'null':
				return is_null( $value );
			default:
				return false;
		}
	}
}
