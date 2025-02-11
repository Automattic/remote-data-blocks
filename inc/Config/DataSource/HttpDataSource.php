<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Config\DataSource;

use RemoteDataBlocks\Config\ArraySerializable;
use RemoteDataBlocks\Validation\ConfigSchemas;
use RemoteDataBlocks\Validation\Validator;
use RemoteDataBlocks\Validation\ValidatorInterface;
use RemoteDataBlocks\WpdbStorage\DataSourceCrud;
use WP_Error;

/**
 * HttpDataSource class
 *
 * Implements the HttpDataSourceInterface to define a generic HTTP data source.
 */
class HttpDataSource extends ArraySerializable implements HttpDataSourceInterface {
	protected const SERVICE_NAME = REMOTE_DATA_BLOCKS_GENERIC_HTTP_SERVICE;
	protected const SERVICE_SCHEMA_VERSION = 1;

	final public function get_display_name(): string {
		return $this->config['display_name'];
	}

	public function get_endpoint(): string {
		return $this->config['endpoint'];
	}

	public function get_request_headers(): array|WP_Error {
		return $this->get_or_call_from_config( 'request_headers' ) ?? [];
	}

	public function get_image_url(): ?string {
		return $this->config['image_url'] ?? null;
	}

	final public function get_service_name(): string {
		return static::SERVICE_NAME;
	}

	/**
	 * @inheritDoc
	 *
	 * NOTE: This method uses late static bindings to allow child classes to
	 * define their own validation schema.
	 */
	public static function from_array( array $config, ?ValidatorInterface $validator = null ): self|WP_Error {
		$service_config = $config['service_config'] ?? [];
		$validator = $validator ?? new Validator( static::get_service_config_schema() );
		$validated = $validator->validate( $service_config );

		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		return parent::from_array(
			array_merge(
				static::map_service_config( $service_config ),
				[
					// Store the exact data used to create the instance to preserve determinism.
					'service' => static::SERVICE_NAME,
					'service_config' => $service_config,
					'uuid' => $config['uuid'] ?? null,
				]
			)
		);
	}

	public static function from_uuid( string $uuid ): DataSourceInterface|WP_Error {
		$config = DataSourceCrud::get_config_by_uuid( $uuid );

		if ( is_wp_error( $config ) ) {
			return $config;
		}

		return static::from_array( $config );
	}

	/**
	 * @inheritDoc
	 */
	public static function from_array_for_migrations( array $config ): self {
		$service_config = $config['service_config'] ?? [];

		return parent::from_array_for_migrations(
			array_merge(
				static::map_service_config( $service_config ),
				[
					// Store the exact data used to create the instance to preserve determinism.
					'service' => static::SERVICE_NAME,
					'service_config' => $service_config,
					'uuid' => $config['uuid'] ?? null,
				]
			)
		);
	}

	/**
	 * @inheritDoc
	 *
	 * TODO: Do we need to sanitize this to prevent leaking sensitive data?
	 */
	final public function to_array(): array {
		return [
			'service' => static::SERVICE_NAME,
			'service_config' => $this->config['service_config'],
			'uuid' => $this->config['uuid'],
		];
	}

	/**
	 * Performs a migration if the config is out of date.
	 */
	final public function perform_migration( array $service_config ): array {
		// By default, we want to have an active data source.
		if ( ! isset( $service_config['active'] ) ) {
			$service_config['active'] = true;
		}

		// By default, we want to have no error.
		if ( ! isset( $service_config['error'] ) ) {
			$service_config['error'] = null;
		}

		if ( static::SERVICE_SCHEMA_VERSION === $service_config['__version'] ) {
			return $service_config;
		}

		$migrated_service_config = $this->migrate_config( $service_config );

		if ( is_wp_error( $migrated_service_config ) ) {
			$service_config['active'] = false;
			$service_config['error'] = $migrated_service_config->get_error_message();
		} else {
			$service_config['__version'] = static::SERVICE_SCHEMA_VERSION;
			$service_config['active'] = true;
			$service_config['error'] = null;
		}

		return $service_config;
	}

	/**
	 * @inheritDoc
	 */
	protected static function get_config_schema(): array {
		return ConfigSchemas::get_http_data_source_config_schema();
	}

	protected static function get_service_config_schema(): array {
		return ConfigSchemas::get_http_data_source_service_config_schema();
	}

	protected static function map_service_config( array $service_config ): array {
		return [
			'display_name' => $service_config['display_name'] ?? static::SERVICE_NAME,
			'endpoint' => $service_config['endpoint'] ?? null, // Invalid, but we won't guess it.
			'request_headers' => $service_config['request_headers'] ?? [],
		];
	}

	/**
	 * Migrates the config to the current schema version.
	 * Can be overridden by child classes to perform custom migrations.
	 */
	protected function migrate_config( array $service_config ): array|WP_Error {
		return $service_config;
	}
}
