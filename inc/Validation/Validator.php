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
			return $this->validateSchema( $this->schema, $data );
	}

	private function validateSchema( array $schema, $data, string $path = '' ): bool|WP_Error {
		if ( isset( $schema['type'] ) ) {
				$typeCheck = $this->checkType( $data, $schema['type'] );
			if ( ! $typeCheck ) {
					return new WP_Error( 'invalid_type', sprintf( __( '%1$s has an invalid type. Expected %2$s, got %3$s.', 'remote-data-blocks' ), $path, $schema['type'], gettype( $data ) ) );
			}
		}

		if ( isset( $schema['properties'] ) && is_array( $schema['properties'] ) ) {
			foreach ( $schema['properties'] as $field => $fieldSchema ) {
				if ( isset( $data[ $field ] ) ) {
					$result = $this->validateSchema( $fieldSchema, $data[ $field ], $path . $field . '.' );
					if ( is_wp_error( $result ) ) {
							return $result;
					}
				}
			}
		}

		if ( isset( $schema['pattern'] ) && is_string( $data ) && ! preg_match( '/' . $schema['pattern'] . '/', $data ) ) {
				return new WP_Error( 'invalid_format', sprintf( __( '%s has an invalid format.', 'remote-data-blocks' ), $path ) );
		}

		if ( isset( $schema['enum'] ) && ! in_array( $data, $schema['enum'] ) ) {
				return new WP_Error( 'invalid_value', sprintf( __( '%s has an invalid value.', 'remote-data-blocks' ), $path ) );
		}

		if ( $schema['type'] === 'array' && isset( $schema['items'] ) ) {
			foreach ( $data as $index => $item ) {
					$result = $this->validateSchema( $schema['items'], $item, $path . '[' . $index . '].' );
				if ( is_wp_error( $result ) ) {
					return $result;
				}
			}
		}

			return true;
	}

	private function checkType( $value, string $expected_type ): bool {
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
