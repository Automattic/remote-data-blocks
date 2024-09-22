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
			return new WP_Error( 'invalid_type', sprintf( __( 'Expected %1$s, got %2$s.', 'remote-data-blocks' ), $schema['type'], gettype( $data ) ) );
		}

		if ( isset( $schema['properties'] ) && is_array( $schema['properties'] ) ) {
			foreach ( $schema['properties'] as $field => $fieldSchema ) {
				if ( isset( $data[ $field ] ) ) {
					$result = $this->validate_schema( $fieldSchema, $data[ $field ] );
					if ( is_wp_error( $result ) ) {
						return $result;
					}
				}
			}
		}

		if ( isset( $schema['pattern'] ) && is_string( $data ) && ! preg_match( '/' . $schema['pattern'] . '/', $data ) ) {
			return new WP_Error( 'invalid_format', sprintf( __( 'Expected %1$s, got %2$s.', 'remote-data-blocks' ), $schema['pattern'], $data ) );
		}

		if ( isset( $schema['enum'] ) && ! in_array( $data, $schema['enum'] ) ) {
			return new WP_Error( 'invalid_value', sprintf( __( 'Expected %1$s, got %2$s.', 'remote-data-blocks' ), implode( ', ', $schema['enum'] ), $data ) );
		}

		if ( $schema['type'] === 'array' && isset( $schema['items'] ) ) {
			foreach ( $data as $index => $item ) {
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
				return is_object( $value ) || ( is_array( $value ) && array_keys( $value ) !== range( 0, count( $value ) - 1 ) );
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
