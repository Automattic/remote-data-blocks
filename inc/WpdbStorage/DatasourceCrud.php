<?php

namespace RemoteDataBlocks\WpdbStorage;

use RemoteDataBlocks\Config\ArraySerializableInterface;
use RemoteDataBlocks\Config\Datasource\HttpDatasourceInterface;
use WP_Error;

use const RemoteDataBlocks\REMOTE_DATA_BLOCKS__DATASOURCE_CLASSMAP;

class DatasourceCrud {
	const CONFIG_OPTION_NAME = 'remote_data_blocks_config';

	/**
	 * Validate the slug to verify
	 * - is not empty
	 * - only contains lowercase alphanumeric characters and hyphens
	 * - is not already taken
	 *
	 * @param string $slug The slug to validate.
	 * @param string [$uuid] The UUID of the data source to exclude from the check.
	 * @return WP_Error|true Returns true if the slug is valid, or a WP_Error object if not.
	 */
	public static function validate_slug( string $slug ): WP_Error|bool {
		if ( empty( $slug ) ) {
			return new WP_Error( 'missing_slug', __( 'Missing slug.', 'remote-data-blocks' ) );
		}

		if ( ! preg_match( '/^[a-z0-9-]+$/', $slug ) ) {
			return new WP_Error( 'invalid_slug', __( 'Invalid slug.', 'remote-data-blocks' ) );
		}

		$data_sources = self::get_data_sources();
	
		$slug_exists = array_filter( $data_sources, function ( $source ) use ( $slug ) {
			return $source->slug === $slug;
		} );

		if ( ! empty( $slug_exists ) ) {
			return new WP_Error( 'slug_already_taken', __( 'Slug already taken.', 'remote-data-blocks' ) );
		}

		return true;
	}

	public static function register_new_data_source( array $settings, ArraySerializableInterface $datasource = null ) {
		$data_sources = self::get_data_sources();

		do {
			$uuid = wp_generate_uuid4();
		} while ( ! empty( self::get_item_by_uuid( self::get_data_sources(), $uuid ) ) );

		$new_datasource = $datasource ?? self::resolve_datasource( array_merge( $settings, [ 'uuid' => $uuid ] ) );

		if ( is_wp_error( $new_datasource ) ) {
			return $new_datasource;
		}

		$result = self::save_datasource( $new_datasource, $data_sources );

		if ( true !== $result ) {
			return new WP_Error( 'failed_to_register_data_source', __( 'Failed to register data source.', 'remote-data-blocks' ) );
		}

		return $new_datasource;
	}

	public static function get_config(): array {
		return get_option( self::CONFIG_OPTION_NAME, [] );
	}

	public static function get_data_sources( string $service = '' ): array {
		$data_sources = self::get_config();

		if ( $service ) {
			return array_values( array_filter($data_sources, function ( $config ) use ( $service ) {
				return $config['service'] === $service;
			} ) );
		}

		return $data_sources;
	}

	public static function get_item_by_uuid( array $data_sources, string $uuid ): array|false {
		return $data_sources[ $uuid ] ?? false;
	}

	public static function update_item_by_uuid( string $uuid, $new_item, ArraySerializableInterface $datasource = null ) {
		$data_sources = self::get_data_sources();
		$item         = self::get_item_by_uuid( $data_sources, $uuid );
		if ( ! $item ) {
			return new WP_Error( 'data_source_not_found', __( 'Data source not found.', 'remote-data-blocks' ), [ 'status' => 404 ] );
		}

		$datasource = $datasource ?? self::resolve_datasource( array_merge( $item, $new_item ) );

		if ( is_wp_error( $datasource ) ) {
			return $datasource;
		}

		$result = self::save_datasource( $datasource, $data_sources );
		
		if ( true !== $result ) {
			return new WP_Error( 'failed_to_update_data_source', __( 'Failed to update data source.', 'remote-data-blocks' ) );
		}
		
		return $new_item;
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

	private static function save_datasource( ArraySerializableInterface $datasource, array $datasource_configs ): bool {
		$config = $datasource->to_array();
		
		if ( ! isset( $config['__metadata'] ) ) {
			$config['__metadata'] = [];
		}

		$now = gmdate( 'Y-m-d H:i:s' );
		
		if ( ! isset( $config['__metadata']['created_at'] ) ) {
			$config['__metadata']['created_at'] = $now;
		}

		$config['__metadata']['updated_at']    = $now;
		$datasource_configs[ $config['uuid'] ] = $config;

		return update_option( self::CONFIG_OPTION_NAME, $datasource_configs );
	}

	private static function resolve_datasource( array $config ): HttpDatasourceInterface|WP_Error {
		if ( isset( REMOTE_DATA_BLOCKS__DATASOURCE_CLASSMAP[ $config['service'] ] ) ) {
			return REMOTE_DATA_BLOCKS__DATASOURCE_CLASSMAP[ $config['service'] ]::from_array( $config );
		}

		return new WP_Error( 'unsupported_datasource', __( 'Datasource class not found.', 'remote-data-blocks' ) );
	}
}
