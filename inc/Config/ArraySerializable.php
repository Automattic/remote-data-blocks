<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Config;

use RemoteDataBlocks\Sanitization\Sanitizer;
use RemoteDataBlocks\Validation\Validator;
use RemoteDataBlocks\Validation\ValidatorInterface;
use WP_Error;

defined( 'ABSPATH' ) || exit();

/**
 * ArraySerializable class
 */
abstract class ArraySerializable implements ArraySerializableInterface {
	final private function __construct( protected array $config ) {}

	protected function get_or_call_from_config( string $property_name, mixed ...$callable_args ): mixed {
		$config_value = $this->config[ $property_name ] ?? null;

		if ( is_callable( $config_value ) ) {
			return call_user_func_array( $config_value, $callable_args );
		}

		return $config_value;
	}

	/**
	 * @inheritDoc
	 */
	public static function from_array( array $config, ?ValidatorInterface $validator = null ): self|WP_Error {
		$schema = static::get_config_schema();

		$validator = $validator ?? new Validator( $schema, static::class );
		$validated = $validator->validate( $config );

		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		$sanitizer = new Sanitizer( $schema );
		$sanitized = $sanitizer->sanitize( $config );

		return new static( $sanitized );
	}

	/**
	 * @inheritDoc
	 */
	public function to_array(): array {
		return $this->config;
	}

	abstract protected static function get_config_schema(): array;
}
