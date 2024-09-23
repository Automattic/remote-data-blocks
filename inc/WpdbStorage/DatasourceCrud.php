<?php

namespace RemoteDataBlocks\WpdbStorage;

use RemoteDataBlocks\Config\ArraySerializableInterface;
use RemoteDataBlocks\Config\Datasource\HttpDatasource;
use RemoteDataBlocks\Config\Datasource\HttpDatasourceInterface;
use WP_Error;

use const RemoteDataBlocks\REMOTE_DATA_BLOCKS__DATASOURCE_CLASSMAP;

class DatasourceCrud {
	const CONFIG_OPTION_NAME = 'remote_data_blocks_config';

	public static function register_new_data_source( array $settings, ArraySerializableInterface $datasource = null ) {
		$data_sources = self::get_data_sources();

		do {
			$uuid = wp_generate_uuid4();
		} while ( ! empty( self::get_item_by_uuid( self::get_data_sources(), $uuid ) ) );

		$new_datasource = $datasource ?? self::resolve_datasource( array_merge( $settings, [ 'uuid' => $uuid ] ) );

		if ( is_wp_error( $new_datasource ) ) {
			return $new_datasource;
		}

		$data_sources[] = $new_datasource->to_array();

		$result = update_option( self::CONFIG_OPTION_NAME, $data_sources );

		if ( true !== $result ) {
			return new WP_Error( 'failed_to_register_data_source', __( 'Failed to register data source.', 'remote-data-blocks' ) );
		}

		return $new_datasource;
	}

	public static function get_config() {
		return (array) get_option( self::CONFIG_OPTION_NAME, [] );
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

	public static function get_item_by_uuid( $data_sources, string $uuid ): array|false {
		$item = array_filter( $data_sources, function ( $source ) use ( $uuid ) {
			return $source['uuid'] === $uuid;
		} );
		return reset( $item );
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

		$updated = $datasource->to_array();

		$data_sources = array_map( function ( $source ) use ( $updated ) {
			return $source['uuid'] === $updated['uuid'] ? $updated : $source;
		}, $data_sources );
		$result       = update_option( self::CONFIG_OPTION_NAME, $data_sources );
		if ( true !== $result ) {
			return new WP_Error( 'failed_to_update_data_source', __( 'Failed to update data source.', 'remote-data-blocks' ) );
		}
		return $new_item;
	}

	public static function delete_item_by_uuid( string $uuid ): WP_Error|bool {
		$data_sources = self::get_data_sources();
		$index        = array_search( $uuid, array_column( $data_sources, 'uuid' ) );
		array_splice( $data_sources, $index, 1 );
		$result = update_option( self::CONFIG_OPTION_NAME, $data_sources );
		if ( true !== $result ) {
			return new WP_Error( 'failed_to_delete_data_source', __( 'Failed to delete data source.', 'remote-data-blocks' ) );
		}
		return true;
	}

	private static function resolve_datasource( array $config ): HttpDatasourceInterface|WP_Error {
		if ( isset( REMOTE_DATA_BLOCKS__DATASOURCE_CLASSMAP[ $config['service'] ] ) ) {
			return REMOTE_DATA_BLOCKS__DATASOURCE_CLASSMAP[ $config['service'] ]::from_array( $config );
		}

		return new WP_Error( 'unsupported_datasource', __( 'Datasource class not found.', 'remote-data-blocks' ) );
	}
}
