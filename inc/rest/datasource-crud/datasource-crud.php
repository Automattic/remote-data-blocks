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
 *     uuid: string,    // Unique identifier for the data source
 *     slug: string,    // URL-display name for the data source
 *     service: string, // Type of service (e.g., 'airtable', 'shopify')
 *     token: string,   // Authentication token for the service
 *     // Additional fields specific to each service type
 * }
 *
 * The array of these objects is stored in the WordPress options table under the key defined
 * by CONFIG_OPTION_NAME.
 */
class DatasourceCRUD {
	const CONFIG_OPTION_NAME = 'remote_data_blocks_config';
	const DATA_SOURCE_TYPES  = [ 'airtable', 'shopify' ];

	public static function is_uuid4( string $maybe_uuid ) {
		return preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $maybe_uuid );
	}

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
	public static function validate_slug( string $slug, string $uuid = '' ): WP_Error|true {
		if ( empty( $slug ) ) {
			return new WP_Error( 'missing_slug', __( 'Missing slug.', 'remote-data-blocks' ) );
		}

		if ( ! preg_match( '/^[a-z0-9-]+$/', $slug ) ) {
			return new WP_Error( 'invalid_slug', __( 'Invalid slug.', 'remote-data-blocks' ) );
		}

		$data_sources = self::get_data_sources();
		$data_sources = array_filter( $data_sources, function ( $source ) use ( $uuid ) {
			return $source->uuid !== $uuid;
		} );

		$slug_exists = array_filter( $data_sources, function ( $source ) use ( $slug ) {
			return $source->slug === $slug;
		} );

		if ( ! empty( $slug_exists ) ) {
			return new WP_Error( 'slug_already_taken', __( 'Slug already taken.', 'remote-data-blocks' ) );
		}

		return true;
	}

	private static function validate_airtable_source( $source ) {
		if ( empty( $source->token ) ) {
			return new WP_Error( 'missing_token', __( 'Missing token.', 'remote-data-blocks' ) );
		}

		// Validate base is not empty and is an object with id and name fields with string values
		if ( empty( $source->base ) ) {
			return new WP_Error( 'missing_base', __( 'Missing base.', 'remote-data-blocks' ) );
		}

		if ( empty( $source->base['id'] ) || empty( $source->base['name'] ) ) {
			return new WP_Error( 'invalid_base', __( 'Invalid base. Must have id and name fields.', 'remote-data-blocks' ) );
		}

		// Validate table is not empty and is an object with id and name fields with string values
		if ( empty( $source->table ) ) {
			return new WP_Error( 'missing_table', __( 'Missing table.', 'remote-data-blocks' ) );
		}

		if ( empty( $source->table['id'] ) || empty( $source->table['name'] ) ) {
			return new WP_Error( 'invalid_table', __( 'Invalid table. Must have id and name fields.', 'remote-data-blocks' ) );
		}

		return [
			'uuid'            => $source->uuid,
			'token'           => sanitize_text_field( $source->token ),
			'service'         => 'airtable',
			'base'            => $source->base,
			'table'           => $source->table,
			'slug'            => sanitize_text_field( $source->slug ),
			// quick hack to transform data to our experimental format
			'display_name'    => sanitize_text_field( $source->slug ),
			'uid'             => hash( 'sha256', $source->slug ),
			'endpoint'        => 'https://api.airtable.com/v0/' . $source->base['id'] . '/' . $source->table['id'],
			'request_headers' => [
				'Authorization' => 'Bearer ' . $source->token,
				'Content-Type'  => 'application/json',
			],
			'image_url'       => null,
		];
	}

	public static function validate_shopify_source( $source ) {
		if ( empty( $source->token ) ) {
			return new WP_Error( 'missing_token', __( 'Missing token.', 'remote-data-blocks' ) );
		}

		return [
			'uuid'            => $source->uuid,
			'token'           => sanitize_text_field( $source->token ),
			'service'         => 'shopify',
			'store'           => sanitize_text_field( $source->store ),
			'slug'            => sanitize_text_field( $source->slug ),
			// quick hack totransform data to our experimental format
			'display_name'    => sanitize_text_field( $source->slug ),
			'uid'             => hash( 'sha256', $source->slug ),
			'endpoint'        => 'https://' . $source->store . '.myshopify.com/api/2024-07/graphql.json',
			'request_headers' => [
				'Content-Type'                      => 'application/json',
				'X-Shopify-Storefront-Access-Token' => $source->token,
			],
			'image_url'       => null,
		];
	}

	public static function validate_source( $source ) {
		if ( ! is_object( $source ) ) {
			return new WP_Error( 'invalid_data_source', __( 'Invalid data source.', 'remote-data-blocks' ) );
		}

		if ( empty( $source->uuid ) ) {
			return new WP_Error( 'missing_uuid', __( 'Missing UUID.', 'remote-data-blocks' ) );
		}

		
		if ( ! self::is_uuid4( $source->uuid ) ) {
			return new WP_Error( 'invalid_uuid', __( 'Invalid UUID.', 'remote-data-blocks' ) );
		}

		$slug_validation = self::validate_slug( $source->slug, $source->uuid );

		if ( is_wp_error( $slug_validation ) ) {
			return $slug_validation;
		}

		if ( ! in_array( $source->service, self::DATA_SOURCE_TYPES ) ) {
			return new WP_Error( 'unsupported_data_source_type', __( 'Unsupported data source type.', 'remote-data-blocks' ) );
		}

		switch ( $source->service ) {
			case 'airtable':
				return self::validate_airtable_source( $source );
			case 'shopify':
				return self::validate_shopify_source( $source );
			default:
				return new WP_Error( 'unsupported_data_source', __( 'Unsupported data source.', 'remote-data-blocks' ) );
		}
	}

	public static function register_new_data_source( $settings ) {
		$data_sources = self::get_data_sources();
		do {
			$uuid = wp_generate_uuid4();
		} while ( ! empty( self::get_item_by_uuid( self::get_data_sources(), $uuid ) ) );

		$item = self::validate_source( (object) [
			...( $settings ?? [] ),
			...compact( 'uuid' ),
		] );

		if ( is_wp_error( $item ) ) {
			return $item;
		}

		$data_sources[] = $item;

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

	public static function get_item_by_uuid( $data_sources, string $uuid ) {
		$item = array_filter( $data_sources, function ( $source ) use ( $uuid ) {
			return $source->uuid === $uuid;
		} );
		return reset( $item );
	}

	public static function update_item_by_uuid( string $uuid, $new_item ) {
		$data_sources = self::get_data_sources();
		$item         = self::get_item_by_uuid( $data_sources, $uuid );
		if ( empty( $item ) ) {
			return new WP_Error( 'data_source_not_found', __( 'Data source not found.', 'remote-data-blocks' ), [ 'status' => 404 ] );
		}
		$new_item     = self::validate_source( (object) array_merge( (array) $item, $new_item ) );
		$data_sources = array_map( function ( $source ) use ( $new_item ) {
			return $source->uuid === $new_item->uuid ? $new_item : $source;
		}, $data_sources );
		$result       = update_option( self::CONFIG_OPTION_NAME, $data_sources );
		if ( true !== $result ) {
			return new WP_Error( 'failed_to_update_data_source', __( 'Failed to update data source.', 'remote-data-blocks' ) );
		}
		return $new_item;
	}

	public static function delete_item_by_uuid( $uuid ) {
		$data_sources = self::get_data_sources();
		$data_sources = array_filter( $data_sources, function ( $source ) use ( $uuid ) {
			return $source->uuid !== $uuid;
		} );
		$result       = update_option( self::CONFIG_OPTION_NAME, $data_sources );
		if ( true !== $result ) {
			return new WP_Error( 'failed_to_delete_data_source', __( 'Failed to delete data source.', 'remote-data-blocks' ) );
		}
		return true;
	}
}
