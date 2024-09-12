<?php

namespace RemoteDataBlocks\REST;

use WP_Error;

/**
 * Handles CRUD operations for remote data sources.
 *
 * This class manages the configuration of remote data sources for the Remote Data Blocks plugin.
 * It provides methods for validating, creating, reading, updating, and deleting data source entries.
 *
 * Core data structure:
 * The class uses a WordPress option to store an array of data source objects. Each object represents
 * a remote data source with the following structure:
 * {
 *     uid: string,    // Unique identifier for the data source
 *     service: string, // Type of service (e.g., 'airtable', 'shopify')
 *     token: string,   // Authentication token for the service
 *     display_name: string, // Display name for the data source
 *     // Additional fields specific to each service type
 * }
 *
 * The array of these objects is stored in the WordPress options table under the key defined
 * by CONFIG_OPTION_NAME.
 */
class DatasourceCRUD {
	const CONFIG_OPTION_NAME = 'remote_data_blocks_config';
	const DATA_SOURCE_TYPES  = [ 'airtable', 'shopify' ];

	private static function validate_airtable_source( array $source ): WP_Error|array {
		if ( empty( $source['token'] ) ) {
			return new WP_Error( 'missing_token', __( 'Missing token.', 'remote-data-blocks' ) );
		}

		// Validate base is not empty and is an object with id and name fields with string values
		if ( empty( $source['base'] ) ) {
			return new WP_Error( 'missing_base', __( 'Missing base.', 'remote-data-blocks' ) );
		}

		if ( empty( $source['base']['id'] ) || empty( $source['base']['name'] ) ) {
			return new WP_Error( 'invalid_base', __( 'Invalid base. Must have id and name fields.', 'remote-data-blocks' ) );
		}

		// Validate table is not empty and is an object with id and name fields with string values
		if ( empty( $source['table'] ) ) {
			return new WP_Error( 'missing_table', __( 'Missing table.', 'remote-data-blocks' ) );
		}

		if ( empty( $source['table']['id'] ) || empty( $source['table']['name'] ) ) {
			return new WP_Error( 'invalid_table', __( 'Invalid table. Must have id and name fields.', 'remote-data-blocks' ) );
		}

		return [
			'token'        => sanitize_text_field( $source['token'] ),
			'service'      => 'airtable',
			'display_name' => sanitize_text_field( $source['slug'] ), // TODO: rename slug on frontend
			'base'         => $source['base'],
			'table'        => $source['table'],
			'uid'          => hash( 'sha256', $source['base'] ),
			'uuid'         => hash( 'sha256', $source['base'] ),
		];
	}

	public static function validate_shopify_source( array $source ): WP_Error|array {
		if ( empty( $source['token'] ) ) {
			return new WP_Error( 'missing_token', __( 'Missing token.', 'remote-data-blocks' ) );
		}

		if ( empty( $source['store'] ) ) {
			return new WP_Error( 'missing_store', __( 'Missing store.', 'remote-data-blocks' ) );
		}

		return [
			'token'        => sanitize_text_field( $source['token'] ),
			'service'      => 'shopify',
			'display_name' => sanitize_text_field( $source['slug'] ), // TODO: rename slug on frontend
			'store'        => sanitize_text_field( $source['store'] ),
			'uid'          => hash( 'sha256', $source['store'] ),
			'uuid'         => hash( 'sha256', $source['store'] ),
		];
	}

	public static function validate_source( array $source ): WP_Error|array {
		if ( ! in_array( $source['service'], self::DATA_SOURCE_TYPES ) ) {
			return new WP_Error( 'unsupported_data_source_type', __( 'Unsupported data source type.', 'remote-data-blocks' ) );
		}

		switch ( $source['service'] ) {
			case 'airtable':
				return self::validate_airtable_source( $source );
			case 'shopify':
				return self::validate_shopify_source( $source );
			default:
				return new WP_Error( 'unsupported_data_source', __( 'Unsupported data source.', 'remote-data-blocks' ) );
		}
	}

	public static function register_new_data_source( array $settings ) {
		$item = self::validate_source( $settings );

		if ( is_wp_error( $item ) ) {
			return $item;
		}

		$data_sources = self::get_data_sources();

		if ( isset( $data_sources[ $item['uid'] ] ) ) {
			return new WP_Error( 'data_source_already_exists', __( 'Data source already exists.', 'remote-data-blocks' ) );
		}

		$data_sources[ $item['uid'] ] = $item;

		$result = update_option( self::CONFIG_OPTION_NAME, $data_sources );

		if ( true !== $result ) {
			return new WP_Error( 'failed_to_register_data_source', __( 'Failed to register data source.', 'remote-data-blocks' ) );
		}

		return $item;
	}

	public static function get_config() {
		return (array) get_option( self::CONFIG_OPTION_NAME, [] );
	}

	public static function get_data_sources( string $service = '' ) {
		$data_sources = self::get_config();

		if ( $service ) {
			return array_filter( $data_sources, function ( $config ) use ( $service ) {
				return $config->service === $service;
			} );
		}

		return $data_sources;
	}

	public static function get_item_by_uid( array $data_sources, string $uid ): ?array {
		return $data_sources[ $uid ] ?? null;
	}

	public static function update_item_by_uid( array $data_sources, string $uid, array $new_item ): WP_Error|array {
		$item = self::get_item_by_uid( $data_sources, $uid );
		if ( ! $item ) {
			return new WP_Error( 'data_source_not_found', __( 'Data source not found.', 'remote-data-blocks' ), [ 'status' => 404 ] );
		}

		$new_item = self::validate_source( array_merge( (array) $item, $new_item ) );
		if ( is_wp_error( $new_item ) ) {
			return $new_item;
		}

		$data_sources[ $uid ] = $new_item;

		$result = update_option( self::CONFIG_OPTION_NAME, $data_sources );
		if ( true !== $result ) {
			return new WP_Error( 'failed_to_update_data_source', __( 'Failed to update data source.', 'remote-data-blocks' ) );
		}

		return $new_item;
	}

	public static function delete_item_by_uid( array $data_sources, string $uid ): WP_Error|true {
		unset( $data_sources[ $uid ] );

		$result = update_option( self::CONFIG_OPTION_NAME, $data_sources );
		if ( true !== $result ) {
			return new WP_Error( 'failed_to_delete_data_source', __( 'Failed to delete data source.', 'remote-data-blocks' ) );
		}

		return true;
	}
}
