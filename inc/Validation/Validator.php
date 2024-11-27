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
	public function validate( array $data ): bool|WP_Error {
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

	private function check_non_primitive_type( array $type, mixed $value ): bool|WP_Error {
		switch ( Types::get_type_name( $type ) ) {
			case 'const':
				return Types::get_type_args( $type ) === $value;

			case 'enum':
				return in_array( $value, Types::get_type_args( $type ), true );

			case 'instance_of':
				return is_a( $value, Types::get_type_args( $type ) );

			case 'list_of':
				if ( ! is_array( $value ) || ! array_is_list( $value ) ) {
					return $this->create_error( 'Value must be an array', $value );
				}

				$member_type = Types::get_type_args( $type );
				return array_reduce( $value, function ( bool $carry, mixed $item ) use ( $member_type ): bool {
					return $carry && $this->check_type( $member_type, $item );
				}, true );

			case 'one_of':
				foreach ( Types::get_type_args( $type ) as $member_type ) {
					if ( $this->check_type( $member_type, $value ) ) {
						return true;
					}
				}

				return $this->create_error( 'Value must be one of the specified types.', $value );

			case 'object':
				if ( ! $this->check_iterable_object( $value ) ) {
					return $this->create_error( 'Value must be an object or associative array', $value );
				}

				foreach ( Types::get_type_args( $type ) as $key => $value_type ) {
					if ( ! $this->check_type( $value_type, $this->get_object_key( $value, $key ) ) ) {
						return $this->create_error( 'Object must have valid field', $value_type );
					}
				}

				return true;

			case 'record':
				if ( ! $this->check_iterable_object( $value ) ) {
					return $this->create_error( 'Value must be an object or associative array', $value );
				}

				$type_args = Types::get_type_args( $type );
				$key_type = $type_args[0];
				$value_type = $type_args[1];

				foreach ( $value as $key => $record_value ) {
					if ( ! $this->check_type( $key_type, $key ) ) {
						return $this->create_error( 'Record must have valid key', $key );
					}

					if ( ! $this->check_type( $value_type, $record_value ) ) {
						return $this->create_error( 'Record must have valid value', $record_value );
					}
				}

				return true;

			case 'ref':
				return $this->check_type( Types::load_ref_type( $type ), $value );

			case 'string_matching':
				$regex = Types::get_type_args( $type );
				return $this->check_primitive_type( 'string', $value ) && $this->check_primitive_type( 'string', $regex ) && preg_match( $regex, $value );

			default:
				return false;
		}
	}

	private function check_primitive_type( string $type_name, mixed $value ): bool {
		switch ( $type_name ) {
			case 'boolean':
				return is_bool( $value );
			case 'callable':
				return is_callable( $value );
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
				return filter_var( $value, FILTER_VALIDATE_URL );

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

	private function create_error( string $message, mixed $value, int $status = 400 ): WP_Error {
		$message = sprintf( '%s: %o', esc_html( $message ), $value );
		return new WP_Error( 'invalid_type', $message, [ 'status' => $status ] );
	}

	private function get_object_key( mixed $data, string $key ): mixed {
		return is_array( $data ) && array_key_exists( $key, $data ) ? $data[ $key ] : null;
	}
}
