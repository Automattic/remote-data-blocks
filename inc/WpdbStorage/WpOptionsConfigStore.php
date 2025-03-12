<?php declare(strict_types = 1);

namespace RemoteDataBlocks\WpdbStorage;

use WP_Error;

/**
 * Abstract base class for WordPress options-based config storage.
 * 
 * This class provides common CRUD operations for configs stored in WordPress options.
 */
abstract class WpOptionsConfigStore {
	/**
	 * Get the error code prefix for this config type.
	 */
	abstract protected static function get_error_code_prefix(): string;
	
	/**
	 * Get the error message prefix for this config type.
	 */
	abstract protected static function get_error_message_prefix(): string;

	/**
	 * Get the WordPress option name used for storing configs.
	 */
	abstract protected static function get_option_name(): string;
	
	/**
	 * Get the config class map for this storage type.
	 *
	 * @return array The class map with service names as keys and config class names as values.
	 */
	abstract protected static function get_config_class_map(): array;

	/**
	 * Validate and instantiate a config object from array data.
	 *
	 * @param array $config The config data.
	 * @return mixed The config instance or an error.
	 */
	protected static function validate_and_instantiate( array $config ): mixed {
		$service = $config['service'] ?? '';

		if ( empty( $service ) ) {
			return new WP_Error(
				'missing_service',
				__( 'Missing service', 'remote-data-blocks' )
			);
		}

		$config_class_map = static::get_config_class_map();
		
		$config_class = $config_class_map[ $service ] ?? null;
		
		if ( null === $config_class ) {
			return new WP_Error(
				'unsupported_service',
				sprintf(
					// translators: %s is the name of the service that is not supported
					__( 'Unsupported service: %s', 'remote-data-blocks' ),
					$service
				)
			);
		}
		
		return $config_class::from_array( $config );
	}

	private static function get_not_found_error_code(): string {
		return static::get_error_code_prefix() . '_not_found';
	}

	private static function get_not_found_error_message(): string {
		// translators: %s is the error message prefix for the config type
		return sprintf( __( '%s not found', 'remote-data-blocks' ), static::get_error_message_prefix() );
	}

	private static function get_save_failed_error_code(): string {
		return 'failed_to_save_' . static::get_error_code_prefix();
	}

	private static function get_save_failed_error_message(): string {
		// translators: %s is the error message prefix for the config type
		return sprintf( __( 'Failed to save %s', 'remote-data-blocks' ), static::get_error_message_prefix() );
	}

	private static function get_delete_failed_error_code(): string {
		return 'failed_to_delete_' . static::get_error_code_prefix();
	}

	private static function get_delete_failed_error_message(): string {
		// translators: %s is the error message prefix for the config type
		return sprintf( __( 'Failed to delete %s', 'remote-data-blocks' ), static::get_error_message_prefix() );
	}

	/**
	 * Get all configs from the database.
	 *
	 * @return array Array of configs.
	 */
	public static function get_all(): array {
		return get_option( static::get_option_name(), [] );
	}

	/**
	 * Get a config by UUID.
	 *
	 * @param string $uuid The UUID of the config.
	 * @return array|WP_Error The config or an error.
	 */
	public static function get_by_uuid( string $uuid ): array|WP_Error {
		foreach ( static::get_all() as $config ) {
			if ( $config['uuid'] === $uuid ) {
				return $config;
			}
		}

		return new WP_Error( 
			static::get_not_found_error_code(), 
			static::get_not_found_error_message(), 
			[ 'status' => 404 ] 
		);
	}

	/**
	 * Get configs by service name.
	 *
	 * @param string $service_name The service name.
	 * @return array Array of configs for the specified service.
	 */
	public static function get_by_service( string $service_name ): array {
		return array_values( array_filter( 
			static::get_all(), 
			function ( $config ) use ( $service_name ): bool {
				return $config['service'] === $service_name;
			} 
		) );
	}

	/**
	 * Create a new config.
	 *
	 * @param array $config The config data.
	 * @return array|WP_Error The created config or an error.
	 */
	public static function create( array $config ): array|WP_Error {
		return static::save( $config );
	}

	/**
	 * Update an existing config.
	 *
	 * @param string $uuid The UUID of the config to update.
	 * @param array $config_data The updated config data.
	 * @return array|WP_Error The updated config or an error.
	 */
	public static function update( string $uuid, array $config_data ): array|WP_Error {
		$existing = static::get_by_uuid( $uuid );
		if ( is_wp_error( $existing ) ) {
			return $existing;
		}

		$config = array_merge( $existing, $config_data );
		$config['uuid'] = $uuid; // Ensure UUID remains the same

		return static::save( $config );
	}

	/**
	 * Save a config to the database.
	 *
	 * @param array $config The config data.
	 * @return array|WP_Error The saved config or an error.
	 */
	protected static function save( array $config ): array|WP_Error {
		// Create a validated config instance
		$config_instance = static::validate_and_instantiate( $config );
		if ( is_wp_error( $config_instance ) ) {
			return $config_instance;
		}
		
		// Convert the instance back to an array
		$new_config = $config_instance->to_array();
		
		// Ensure metadata is set
		$now = gmdate( 'Y-m-d H:i:s' );
		$new_config['__metadata'] = [
			'created_at' => $config['__metadata']['created_at'] ?? $now,
			'updated_at' => $now,
		];
		
		// Ensure UUID is set
		$new_config['uuid'] = $config['uuid'] ?? wp_generate_uuid4();

		// Create or update the config
		$configs = array_values( array_filter( 
			static::get_all(), 
			function ( $existing ) use ( $new_config ) {
				return $existing['uuid'] !== $new_config['uuid'];
			} 
		) );
		$configs[] = $new_config;

		if ( true !== static::save_all( $configs ) ) {
			return new WP_Error( 
				static::get_save_failed_error_code(), 
				static::get_save_failed_error_message()
			);
		}

		return $new_config;
	}

	/**
	 * Delete a config by UUID.
	 *
	 * @param string $uuid The UUID of the config to delete.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public static function delete( string $uuid ): bool|WP_Error {
		$configs = array_values( array_filter( 
			static::get_all(), 
			function ( $config ) use ( $uuid ) {
				return $config['uuid'] !== $uuid;
			} 
		) );

		if ( true !== static::save_all( $configs ) ) {
			return new WP_Error( 
				static::get_delete_failed_error_code(), 
				static::get_delete_failed_error_message()
			);
		}

		return true;
	}

	/**
	 * Save all configs to the database.
	 */
	protected static function save_all( array $configs ): bool {
		return update_option( static::get_option_name(), $configs );
	}
}
