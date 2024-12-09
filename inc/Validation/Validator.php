<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Validation;

use RemoteDataBlocks\Validation\Types;
use WP_Error;
use function is_email;

/**
 * Validator class.
 */
final class Validator implements ValidatorInterface {
	public function __construct( private array $schema ) {}

	/**
	 * @inheritDoc
	 */
	public function validate( mixed $data ): bool|WP_Error {
		$base_validation = $this->check_type( $this->schema, $data );

		if ( is_wp_error( $base_validation ) ) {
			return $base_validation;
		}

		return true;
	}

	/**
	 * Validate a value recursively against a schema.
	 *
	 * @param array<string, mixed> $type The schema to validate against.
	 * @param mixed $value The value to validate.
	 * @return bool|WP_Error Returns true if the data is valid, otherwise a WP_Error.
	 */
	private function check_type( array $type, mixed $value = null ): bool|WP_Error {
		if ( Types::is_nullable( $type ) && is_null( $value ) ) {
			return true;
		}

		if ( Types::is_primitive( $type ) ) {
			$type_name = Types::get_type_name( $type );
			if ( $this->check_primitive_type( $type_name, $value ) ) {
				return true;
			}

			return $this->create_error( sprintf( 'Value must be a %s', $type_name ), $value );
		}

		return $this->check_non_primitive_type( $type, $value );
	}

	/**
	 * Validate a non-primitive value against a schema. This method returns true
	 * or a WP_Error object. Never check the return value for truthiness; either
	 * return the value directly or check it with is_wp_error().
	 *
	 * @param array<string, mixed> $type The schema type to validate against.
	 * @param mixed $value The value to validate.
	 * @return bool|WP_Error Returns true if the data is valid, otherwise a WP_Error.
	 */
	private function check_non_primitive_type( array $type, mixed $value ): true|WP_Error {
		switch ( Types::get_type_name( $type ) ) {
			case 'callable':
				if ( is_callable( $value ) ) {
					return true;
				}

				return $this->create_error( 'Value must be callable', $value );

			case 'const':
				if ( Types::get_type_args( $type ) === $value ) {
					return true;
				}

				return $this->create_error( 'Value must be the constant', $value );

			case 'enum':
				if ( in_array( $value, Types::get_type_args( $type ), true ) ) {
					return true;
				}

				return $this->create_error( 'Value must be one of the enumerated values', $value );

			case 'instance_of':
				if ( is_a( $value, Types::get_type_args( $type ) ) ) {
					return true;
				}

				return $this->create_error( 'Value must be an instance of the specified class', $value );

			case 'list_of':
				if ( ! is_array( $value ) || ! array_is_list( $value ) ) {
					return $this->create_error( 'Value must be a non-associative array', $value );
				}

				$member_type = Types::get_type_args( $type );

				foreach ( $value as $item ) {
					$validated = $this->check_type( $member_type, $item );
					if ( is_wp_error( $validated ) ) {
						return $this->create_error( 'Value must be a list of the specified type', $item, $validated );
					}
				}

				return true;

			case 'one_of':
				foreach ( Types::get_type_args( $type ) as $member_type ) {
					if ( true === $this->check_type( $member_type, $value ) ) {
						return true;
					}
				}

				return $this->create_error( 'Value must be one of the specified types', $value );

			case 'object':
				if ( ! $this->check_iterable_object( $value ) ) {
					return $this->create_error( 'Value must be an associative array', $value );
				}

				foreach ( Types::get_type_args( $type ) as $key => $property_type ) {
					$property_value = $this->get_object_key( $value, $key );
					$validated = $this->check_type( $property_type, $property_value );
					if ( is_wp_error( $validated ) ) {
						return $this->create_error( 'Object must have valid property', $key, $validated );
					}
				}

				return true;

			case 'record':
				if ( ! $this->check_iterable_object( $value ) ) {
					return $this->create_error( 'Value must be an associative array', $value );
				}

				$type_args = Types::get_type_args( $type );
				$key_type = $type_args[0];
				$value_type = $type_args[1];

				foreach ( $value as $key => $record_value ) {
					$validated = $this->check_type( $key_type, $key );
					if ( is_wp_error( $validated ) ) {
						return $this->create_error( 'Record must have valid key', $key );
					}

					$validated = $this->check_type( $value_type, $record_value );
					if ( is_wp_error( $validated ) ) {
						return $this->create_error( 'Record must have valid value', $record_value );
					}
				}

				return true;

			case 'ref':
				return $this->check_type( Types::load_ref_type( $type ), $value );

			case 'string_matching':
				$regex = Types::get_type_args( $type );

				if ( $this->check_primitive_type( 'string', $value ) && $this->check_primitive_type( 'string', $regex ) && preg_match( $regex, $value ) ) {
					return true;
				}

				return $this->create_error( 'Value must match the specified regex', $value );

			default:
				return $this->create_error( 'Unknown type', Types::get_type_name( $type ) );
		}
	}

	private function check_primitive_type( string $type_name, mixed $value ): bool {
		switch ( $type_name ) {
			case 'boolean':
				return is_bool( $value );
			case 'integer':
				return is_int( $value );
			case 'null':
				return is_null( $value );
			case 'number':
				return is_numeric( $value );
			case 'string':
				return is_string( $value );

			case 'base64_string':
				return is_string( $value ) && base64_encode( base64_decode( $value ) ) === $value;

			case 'email_address':
				return is_email( $value );

			case 'html':
			case 'id':
			case 'image_alt':
				return is_string( $value );

			case 'json_path':
				return is_string( $value ) && str_starts_with( $value, '$' );

			case 'price':
				return is_string( $value ) || is_numeric( $value );

			case 'image_url':
			case 'url':
				return false !== filter_var( $value, FILTER_VALIDATE_URL );

			case 'uuid':
				return wp_is_uuid( $value );

			default:
				return false;
		}
	}

	/*
	 * While an "object" in name, we expect this type to be implemented as an
	 * associative array since this is typically how humans represent objects in
	 * literal PHP code.
	 */
	private function check_iterable_object( mixed $value ): bool {
		return is_object( $value ) || ( is_array( $value ) && ! array_is_list( $value ) );
	}

	private function create_error( string $message, mixed $value, ?WP_Error $child_error = null ): WP_Error {
		$serialized_value = is_string( $value ) ? $value : wp_json_encode( $value );
		$message = sprintf( '%s: %s', esc_html( $message ), $serialized_value );
		return new WP_Error( 'invalid_type', $message, [ 'child' => $child_error ] );
	}

	private function get_object_key( mixed $data, string $key ): mixed {
		return is_array( $data ) && array_key_exists( $key, $data ) ? $data[ $key ] : null;
	}
}
