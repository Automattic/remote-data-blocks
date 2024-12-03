<?php declare(strict_types = 1);

namespace RemoteDataBlocks\WpdbStorage;

use RemoteDataBlocks\Config\ArraySerializableInterface;
use RemoteDataBlocks\Config\DataSource\HttpDataSourceInterface;
use WP_Error;

use const RemoteDataBlocks\REMOTE_DATA_BLOCKS__DATA_SOURCE_CLASSMAP;

class DataSourceCrud {
	const CONFIG_OPTION_NAME = 'remote_data_blocks_config';

	public static function register_new_data_source( array $settings, ?ArraySerializableInterface $data_source = null ): HttpDataSourceInterface|WP_Error {
		$data_sources = self::get_data_sources();

		do {
			$uuid = wp_generate_uuid4();
		} while ( ! empty( self::get_item_by_uuid( self::get_data_sources(), $uuid ) ) );

		$new_data_source = $data_source ?? self::resolve_data_source( array_merge( $settings, [ 'uuid' => $uuid ] ) );

		if ( is_wp_error( $new_data_source ) ) {
			return $new_data_source;
		}

		$result = self::save_data_source( $new_data_source, $data_sources );

		if ( true !== $result ) {
			return new WP_Error( 'failed_to_register_data_source', __( 'Failed to register data source.', 'remote-data-blocks' ) );
		}

		return $new_data_source;
	}

	public static function get_config(): array {
		return get_option( self::CONFIG_OPTION_NAME, [] );
	}

	public static function get_data_sources( string $service = '' ): array {
		$data_sources = self::get_config();

		if ( $service ) {
			return array_values( array_filter( $data_sources, function ( $config ) use ( $service ) {
				return $config['service'] === $service;
			} ) );
		}

		return $data_sources;
	}

	/**
	 * Get the array list of data sources
	 */
	public static function get_data_sources_list(): array {
		return array_values( self::get_data_sources() );
	}

	public static function get_item_by_uuid( array $data_sources, string $uuid ): array|false {
		return $data_sources[ $uuid ] ?? false;
	}

	public static function update_item_by_uuid( string $uuid, array $new_item ): HttpDataSourceInterface|WP_Error {
		$data_sources = self::get_data_sources();
		$item = self::get_item_by_uuid( $data_sources, $uuid );
		
		if ( ! $item ) {
			return new WP_Error( 'data_source_not_found', __( 'Data source not found.', 'remote-data-blocks' ), [ 'status' => 404 ] );
		}
	
		// Check if new UUID is provided
		$new_uuid = $new_item['uuid'] ?? null;
		if ( $new_uuid && $new_uuid !== $uuid ) {
			// Ensure the new UUID doesn't already exist
			if ( self::get_item_by_uuid( $data_sources, $new_uuid ) ) {
				return new WP_Error( 'uuid_conflict', __( 'The new UUID already exists.', 'remote-data-blocks' ), [ 'status' => 409 ] );
			}
	
			// Remove the old item from data source array if UUID is being updated
			unset( $data_sources[ $uuid ] );
		}
	
		// Merge new item properties
		$merged_item = array_merge( $item, $new_item );
	
		// Resolve and save the updated item
		$resolved_data_source = self::resolve_data_source( $merged_item );
		if ( is_wp_error( $resolved_data_source ) ) {
			return $resolved_data_source;  // If resolving fails, return error
		}
	
		// Save the updated item
		$result = self::save_data_source( $resolved_data_source, $data_sources, $uuid );  // Passing old UUID to remove it if changed
		if ( !$result ) {
			return new WP_Error( 'failed_to_update_data_source', __( 'Failed to update data source.', 'remote-data-blocks' ) );
		}
	
		return $resolved_data_source;
	}
	

	public static function delete_item_by_uuid( string $uuid ): WP_Error|bool {
		$data_sources = self::get_data_sources();
		unset( $data_sources[ $uuid ] );
		$result = update_option( self::CONFIG_OPTION_NAME, $data_sources );
		if ( true !== $result ) {
			return new WP_Error( 'failed_to_delete_data_source', __( 'Failed to delete data source.', 'remote-data-blocks' ) );
		}
		return true;
	}

	public static function get_by_uuid( string $uuid ): array|false {
		$data_sources = self::get_data_sources();
		foreach ( $data_sources as $source ) {
			if ( $source['uuid'] === $uuid ) {
				return $source;
			}
		}
		return false;
	}

	private static function save_data_source( ArraySerializableInterface $data_source, array $data_source_configs, ?string $original_uuid = null ): bool {
		$config = $data_source->to_array();
		
		if ( ! isset( $config['__metadata'] ) ) {
			$config['__metadata'] = [];
		}

		$now = gmdate( 'Y-m-d H:i:s' );
		$config['__metadata']['updated_at'] = $now;
	
		if ( ! isset( $config['__metadata']['created_at'] ) ) {
			$config['__metadata']['created_at'] = $now;
		}
	
		// If the UUID has changed, remove the old entry based on the original UUID
		if ( $original_uuid && $original_uuid !== $config['uuid'] ) {
			unset( $data_source_configs[ $original_uuid ] );  // Remove old item if UUID is changing
		}
	
		// Add or update the data source with the new UUID
		$data_source_configs[ $config['uuid'] ] = $config;
	
		// Save updated configuration
		return update_option( self::CONFIG_OPTION_NAME, $data_source_configs );
	}

	private static function resolve_data_source( array $config ): HttpDataSourceInterface|WP_Error {
		if ( isset( REMOTE_DATA_BLOCKS__DATA_SOURCE_CLASSMAP[ $config['service'] ] ) ) {
			return REMOTE_DATA_BLOCKS__DATA_SOURCE_CLASSMAP[ $config['service'] ]::from_array( $config );
		}

		return new WP_Error( 'unsupported_data_source', __( 'DataSource class not found.', 'remote-data-blocks' ) );
	}
}
