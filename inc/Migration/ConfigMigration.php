<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Migration;

use RemoteDataBlocks\WpdbStorage\DataSourceCrud;
use RemoteDataBlocks\WpdbStorage\QueryCrud;
use WP_Error;

/**
 * Handles migration of configs from old format to new format.
 */
class ConfigMigration {
	/**
	 * Migrate a single data source config to separate data source and query configs.
	 *
	 * @param array $config The config to migrate.
	 * @return array|WP_Error The migrated data source and query configs.
	 */
	public static function migrate_config( array $config ): array|WP_Error {
		$service = $config['service'] ?? null;
		if ( ! $service ) {
			return new WP_Error(
				'invalid_config',
				__( 'Invalid config: missing service', 'remote-data-blocks' )
			);
		}

		$service_config = $config['service_config'] ?? [];
		$uuid = $config['uuid'] ?? null;

		// Create a data source config that only contains auth fields
		$data_source_config = [
			'service' => $service,
			'service_config' => self::extract_data_source_fields( $service, $service_config ),
		];

		// If the original config has metadata, preserve it
		if ( isset( $config['__metadata'] ) ) {
			$data_source_config['__metadata'] = $config['__metadata'];
		}

		// Create or update the data source
		if ( $uuid ) {
			$data_source = DataSourceCrud::get_config_by_uuid( $uuid );
			if ( ! is_wp_error( $data_source ) ) {
				// If a data source with this UUID exists, update it
				$data_source = DataSourceCrud::update_config_by_uuid( $uuid, $data_source_config['service_config'] );
			} else {
				// Otherwise, create a new data source and get its UUID
				$data_source = DataSourceCrud::create_config( $data_source_config );
			}
		} else {
			// Create a new data source and get its UUID
			$data_source = DataSourceCrud::create_config( $data_source_config );
		}

		if ( is_wp_error( $data_source ) ) {
			return $data_source;
		}

		// Create a query config that contains scope fields and references the data source
		$query_config = [
			'service' => $service,
			'data_source_uuid' => $data_source['uuid'],
			'query_config' => self::extract_query_fields( $service, $service_config ),
		];

		// Create or update the query
		$query = QueryCrud::create_config( $query_config );

		if ( is_wp_error( $query ) ) {
			return $query;
		}

		return [
			'data_source' => $data_source,
			'query' => $query,
		];
	}

	/**
	 * Migrate all existing configs to the new format.
	 *
	 * @return array Migration results.
	 */
	public static function migrate_all_configs(): array {
		$configs = DataSourceCrud::get_configs();
		$results = [
			'success' => [],
			'error' => [],
		];

		foreach ( $configs as $config ) {
			$result = self::migrate_config( $config );
			if ( is_wp_error( $result ) ) {
				$results['error'][] = [
					'config' => $config,
					'error' => $result->get_error_message(),
				];
			} else {
				$results['success'][] = $result;
			}
		}

		return $results;
	}

	/**
	 * Extract data source fields from a service config based on service type.
	 *
	 * @param string $service The service type.
	 * @param array $service_config The service config.
	 * @return array Data source fields.
	 */
	private static function extract_data_source_fields( string $service, array $service_config ): array {
		$data_source_fields = [
			'display_name' => $service_config['display_name'] ?? '',
		];

		switch ( $service ) {
			case 'http':
				$data_source_fields['endpoint'] = $service_config['endpoint'] ?? '';
				$data_source_fields['request_headers'] = $service_config['request_headers'] ?? [];
				break;

			case 'airtable':
				$data_source_fields['access_token'] = $service_config['access_token'] ?? '';
				break;

			case 'google-sheets':
				$data_source_fields['credentials'] = $service_config['credentials'] ?? null;
				break;

			case 'shopify':
				$data_source_fields['store_name'] = $service_config['store_name'] ?? '';
				$data_source_fields['access_token'] = $service_config['access_token'] ?? '';
				break;

			case 'salesforce-d2c':
				$data_source_fields['domain'] = $service_config['domain'] ?? '';
				$data_source_fields['client_id'] = $service_config['client_id'] ?? '';
				$data_source_fields['client_secret'] = $service_config['client_secret'] ?? '';
				break;
		}

		return $data_source_fields;
	}

	/**
	 * Extract query fields from a service config based on service type.
	 *
	 * @param string $service The service type.
	 * @param array $service_config The service config.
	 * @return array Query fields.
	 */
	private static function extract_query_fields( string $service, array $service_config ): array {
		$query_fields = [
			'enable_blocks' => $service_config['enable_blocks'] ?? true,
		];

		switch ( $service ) {
			case 'airtable':
				$query_fields['base'] = $service_config['base'] ?? null;
				$query_fields['tables'] = $service_config['tables'] ?? [];
				$query_fields['query_type'] = 'airtable';
				break;

			case 'google-sheets':
				$query_fields['spreadsheet'] = $service_config['spreadsheet'] ?? null;
				$query_fields['sheets'] = $service_config['sheets'] ?? [];
				$query_fields['query_type'] = 'google-sheets';
				break;

			case 'shopify':
				$query_fields['query_type'] = 'product';
				break;

			case 'salesforce-d2c':
				$query_fields['store_id'] = $service_config['store_id'] ?? '';
				$query_fields['query_type'] = 'product';
				break;
		}

		return $query_fields;
	}
}