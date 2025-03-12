<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Config\DataSource;

use RemoteDataBlocks\Config\ArraySerializable;
use RemoteDataBlocks\Validation\ConfigSchemas;
use RemoteDataBlocks\Validation\Validator;
use WP_Error;

/**
 * Base class for data source connection configurations.
 * 
 * This class represents the authentication and connection information
 * for a data source, separate from the query-specific parameters.
 */
abstract class DataSourceConnection extends ArraySerializable implements DataSourceConnectionInterface {
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
			'service_config' => $service_config,
			'queries' => $config['queries'] ?? [],
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

	public function get_service_config(): array {
		return $this->config['service_config'];
	}

	/**
	 * Get the schema for validating the data source configuration.
	 *
	 * @return array The schema for validating the data source configuration.
	 */
	public static function get_config_schema(): array {
		return ConfigSchemas::get_data_source_connection_config_schema();
	}

	/**
	 * Get the schema for validating the service-specific configuration.
	 *
	 * @return array The schema for validating the service-specific configuration.
	 */
	abstract public static function get_service_config_schema(): array;

	/**
	 * Get the UUID of the connection.
	 *
	 * @return string The UUID.
	 */
	public function get_uuid(): string {
		return $this->config['uuid'];
	}

	/**
	 * Get the display name of the data source.
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
	abstract public function get_service_name(): string;

	/**
	 * Get the queries for the data source.
	 *
	 * @return array The queries.
	 */
	abstract public function get_queries(): array;
}
