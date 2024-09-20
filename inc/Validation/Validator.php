<?php

namespace RemoteDataBlocks\Validation;

use JsonPath\JsonObject;
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
	public function validate( string|array|object|null $data ): bool|WP_Error {
		$json_data = new JsonObject( $data );

		foreach ( $this->schema as $field => $rules ) {
			$value = $json_data->get( $rules['path'] );

			if ( isset( $rules['required'] ) && $rules['required'] && empty( $value ) ) {
				return new WP_Error( 'missing_field', sprintf( __( '%s is required.', 'remote-data-blocks' ), $field ) );
			}

			if ( isset( $rules['type'] ) && ! $this->checkType( $value, $rules['type'] ) ) {
				$msg = sprintf( __( '%1$s has an invalid type. Expected %2$s, got %3$s.', 'remote-data-blocks' ), $field, $rules['type'], gettype( $value ) );
				return new WP_Error( 'invalid_type', $msg );
			}

			if ( isset( $rules['pattern'] ) && ! preg_match( $rules['pattern'], $value ) ) {
				return new WP_Error( 'invalid_format', sprintf( __( '%s has an invalid format.', 'remote-data-blocks' ), $field ) );
			}

			if ( isset( $rules['enum'] ) && ! in_array( $value, $rules['enum'] ) ) {
				return new WP_Error( 'invalid_value', sprintf( __( '%s has an invalid value.', 'remote-data-blocks' ), $field ) );
			}

			if ( isset( $rules['callback'] ) && is_callable( $rules['callback'] ) ) {
				$callback_check = call_user_func( $rules['callback'], $value );
				if ( ! $callback_check ) {
					return new WP_Error( 'invalid_callback', sprintf( __( '%s failed callback ' . $rules['callback'], 'remote-data-blocks' ), $field ) );
				}
			}
		}

		return true;
	}

	private function checkType( $value, string $expected_type ): bool {
		return gettype( $value ) === $expected_type;
	}
}
