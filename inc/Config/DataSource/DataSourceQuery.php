<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Config\DataSource;

use RemoteDataBlocks\Config\ArraySerializable;
use RemoteDataBlocks\Validation\ConfigSchemas;
use RemoteDataBlocks\Validation\Validator;
use WP_Error;

/**
 * Base class for data source query configurations.
 * 
 * This class represents the query-specific parameters for a data source,
 * separate from the connection/authentication information.
 */
abstract class DataSourceQuery extends ArraySerializable implements DataSourceQueryInterface {
	/**
	 * @inheritDoc
	 */
	public static function preprocess_config( array $config ): array|WP_Error {
		$service_config = $config['service_config'] ?? [];
		$validator = new Validator( static::get_service_config_schema() );
		$validated = $validator->validate( $service_config );

		if ( is_wp_error( $validated ) ) {
			return $validated;
		}
		
		return [
			'service' => static::get_service_name(),
			'uuid' => $config['uuid'] ?? null,
			'connection_uuid' => $config['connection_uuid'] ?? null,
			'service_config' => $service_config,
		];
	}

	/**
	 * @inheritDoc
	 */
	public static function migrate_config( array $config ): array|WP_Error {
		return $config;
	}

	/**
	 * @inheritDoc
	 */
	public function to_array(): array {
		return array_merge(
			$this->config,
			[
				self::CLASS_REF_ATTRIBUTE => static::class,
				'service' => static::get_service_name(),
			]
		);
	}

	/**
	 * Get the schema for validating the query configuration.
	 *
	 * @return array The schema for validating the query configuration.
	 */
	public static function get_config_schema(): array {
		return ConfigSchemas::get_data_source_query_config_schema();
	}

	/**
	 * Get the schema for validating the service-specific configuration.
	 *
	 * @return array The schema for validating the service-specific configuration.
	 */
	abstract public static function get_service_config_schema(): array;

	/**
	 * Get the UUID of the query.
	 *
	 * @return string The UUID.
	 */
	public function get_uuid(): string {
		return $this->config['uuid'];
	}

	/**
	 * Get the UUID of the associated data source connection.
	 *
	 * @return string|null The connection UUID.
	 */
	public function get_connection_uuid(): string|null {
		return $this->config['connection_uuid'];
	}

	/**
	 * Get the display name of the query.
	 *
	 * @return string The display name.
	 */
	public function get_display_name(): string {
		return $this->config['service_config']['display_name'];
	}

	/**
	 * Get the service name.
	 *
	 * @return string The service name.
	 */
	abstract public static function get_service_name(): string;
} 
