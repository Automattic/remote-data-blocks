<?php declare(strict_types = 1);

namespace RemoteDataBlocks\WpdbStorage;

use WP_Error;

class QueryCrud {
	const CONFIG_OPTION_NAME = 'remote_data_blocks_queries';

	public static function create_config( array $config ): array|WP_Error {
		return self::save_config( $config );
	}

	public static function delete_config_by_uuid( string $uuid ): bool|WP_Error {
		$configs = array_values( array_filter( self::get_configs(), function ( $config ) use ( $uuid ) {
			return $config['uuid'] !== $uuid;
		} ) );

		if ( true !== self::save_configs( $configs ) ) {
			return new WP_Error(
				'failed_to_delete_query',
				__( 'Failed to delete query', 'remote-data-blocks' )
			);
		}

		return true;
	}

	public static function get_config_by_uuid( string $uuid ): array|WP_Error {
		foreach ( self::get_configs() as $config ) {
			if ( $config['uuid'] === $uuid ) {
				return $config;
			}
		}

		return new WP_Error(
			'query_not_found',
			__( 'Query not found', 'remote-data-blocks' ),
			[ 'status' => 404 ]
		);
	}

	public static function get_configs(): array {
		return self::get_all_configs();
	}

	public static function get_configs_by_data_source( string $data_source_uuid ): array {
		return array_values( array_filter( self::get_configs(), function ( $config ) use ( $data_source_uuid ): bool {
			return isset( $config['data_source_uuid'] ) && $config['data_source_uuid'] === $data_source_uuid;
		} ) );
	}

	public static function get_configs_by_service( string $service_name ): array {
		return array_values( array_filter( self::get_configs(), function ( $config ) use ( $service_name ): bool {
			return $config['service'] === $service_name;
		} ) );
	}

	public static function update_config_by_uuid( string $uuid, array $query_config ): array|WP_Error {
		$config = self::get_config_by_uuid( $uuid );

		if ( is_wp_error( $config ) ) {
			return $config;
		}

		// Merge the new query config with the existing one.
		$config['query_config'] = array_merge( $config['query_config'] ?? [], $query_config );

		return self::save_config( $config );
	}

	private static function get_all_configs(): array {
		return get_option( self::CONFIG_OPTION_NAME, [] );
	}

	private static function save_config( array $config ): array|WP_Error {
		// Ensure data_source_uuid is set
		if ( empty( $config['data_source_uuid'] ) ) {
			return new WP_Error(
				'missing_data_source',
				__( 'Data source UUID is required', 'remote-data-blocks' )
			);
		}

		// Check if data source exists
		$data_source = DataSourceCrud::get_config_by_uuid( $config['data_source_uuid'] );
		if ( is_wp_error( $data_source ) ) {
			return new WP_Error(
				'invalid_data_source',
				__( 'Invalid data source UUID', 'remote-data-blocks' )
			);
		}

		// Update metadata.
		$now = gmdate( 'Y-m-d H:i:s' );
		$new_config = [
			'__metadata' => [
				'created_at' => $config['__metadata']['created_at'] ?? $now,
				'updated_at' => $now,
			],
			'service' => $config['service'] ?? $data_source['service'],
			'query_config' => $config['query_config'] ?? [],
			'data_source_uuid' => $config['data_source_uuid'],
			'uuid' => $config['uuid'] ?? wp_generate_uuid4(),
		];

		// Create or update the query.
		$configs = array_values( array_filter( self::get_configs(), function ( $existing ) use ( $new_config ) {
			return $existing['uuid'] !== $new_config['uuid'];
		} ) );
		$configs[] = $new_config;

		if ( true !== self::save_configs( $configs ) ) {
			return new WP_Error(
				'failed_to_save_query',
				__( 'Failed to save query', 'remote-data-blocks' )
			);
		}

		return $new_config;
	}

	private static function save_configs( array $configs ): bool|WP_Error {
		return update_option( self::CONFIG_OPTION_NAME, $configs );
	}
}