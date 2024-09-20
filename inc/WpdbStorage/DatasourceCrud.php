<?php

namespace RemoteDataBlocks\WpdbStorage;

use RemoteDataBlocks\Integrations\Google\Auth\GoogleServiceAccountKey;
use WP_Error;

class DatasourceCrud {
	const CONFIG_OPTION_NAME = 'remote_data_blocks_config';
	const DATA_SOURCE_TYPES  = [ 'airtable', 'shopify', 'google-sheets', 'rest-api', 'graphql' ];

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
	public static function validate_slug( string $slug, string $uuid = '' ): WP_Error|bool {
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

	public static function validate_airtable_source( $source ) {
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

		return (object) [
			'uuid'    => $source->uuid,
			'token'   => sanitize_text_field( $source->token ),
			'service' => 'airtable',
			'base'    => $source->base,
			'table'   => $source->table,
			'slug'    => sanitize_text_field( $source->slug ),
		];
	}

	public static function validate_shopify_source( $source ) {
		if ( empty( $source->token ) ) {
			return new WP_Error( 'missing_token', __( 'Missing token.', 'remote-data-blocks' ) );
		}

		return (object) [
			'uuid'    => $source->uuid,
			'token'   => sanitize_text_field( $source->token ),
			'service' => 'shopify',
			'store'   => sanitize_text_field( $source->store ),
			'slug'    => sanitize_text_field( $source->slug ),
		];
	}

	public static function validate_google_sheets_source( $source ) {
		$service_account_key = GoogleServiceAccountKey::from_array( $source->credentials );
		if ( is_wp_error( $service_account_key ) ) {
			return $service_account_key;
		}

		// Validate spreadsheet is not empty and is an object with id and name fields with string values
		if ( empty( $source->spreadsheet ) ) {
			return new WP_Error( 'missing_spreadsheet', __( 'Missing spreadsheet.', 'remote-data-blocks' ) );
		}

		if ( empty( $source->spreadsheet['id'] ) || empty( $source->spreadsheet['name'] ) ) {
			return new WP_Error( 'invalid_spreadsheet', __( 'Invalid spreadsheet. Must have id and name fields.', 'remote-data-blocks' ) );
		}

		// Validate sheet is not empty and is an object with id integer and name string fields
		if ( empty( $source->sheet ) ) {
			return new WP_Error( 'missing_sheet', __( 'Missing sheet.', 'remote-data-blocks' ) );
		}

		if ( ! isset( $source->sheet['id'] ) || ! is_int( $source->sheet['id'] ) ) {
			return new WP_Error( 'invalid_sheet', __( 'Invalid sheet. Must have id field with integer value.', 'remote-data-blocks' ) );
		}

		if ( empty( $source->sheet['name'] ) ) {
			return new WP_Error( 'missing_sheet_name', __( 'Missing sheet name.', 'remote-data-blocks' ) );
		}

		return (object) [
			'uuid'        => $source->uuid,
			'service'     => 'google-sheets',
			'credentials' => $service_account_key,
			'spreadsheet' => $source->spreadsheet,
			'sheet'       => $source->sheet,
			'slug'        => sanitize_text_field( $source->slug ),
		];
	}

	public static function validate_rest_api_source( $source ) {
		if ( empty( $source->url ) ) {
			return new WP_Error( 'missing_url', __( 'Missing URL.', 'remote-data-blocks' ) );
		}
		
		if ( empty( $source->method ) || ! in_array( $source->method, [ 'GET', 'POST' ], true ) ) {
			return new WP_Error( 'invalid_method', __( 'Invalid HTTP method. Must be either GET or POST.', 'remote-data-blocks' ) );
		}

		if ( empty( $source->auth ) ) {
			return new WP_Error( 'missing_auth', __( 'Missing authentication.', 'remote-data-blocks' ) );
		}

		if ( ! in_array( $source->auth['type'], [ 'bearer', 'basic', 'api-key' ], true ) ) {
			return new WP_Error( 'invalid_auth_type', __( 'Invalid authentication type. Must be either bearer, basic, or api-key.', 'remote-data-blocks' ) );
		}

		if ( empty( $source->auth['value'] ) ) {
			return new WP_Error( 'missing_auth_value', __( 'Missing authentication value.', 'remote-data-blocks' ) );
		}

		if ( 'api-key' === $source->auth['type'] && empty( $source->auth['key'] ) ) {
			return new WP_Error( 'missing_api_key', __( 'Missing API key.', 'remote-data-blocks' ) );
		}

		if ( 'api-key' === $source->auth['type'] && empty( $source->auth['add_to'] ) ) {
			return new WP_Error( 'missing_api_key_add_to', __( 'Missing API key add to.', 'remote-data-blocks' ) );
		}

		return (object) [
			'uuid'    => $source->uuid,
			'service' => 'rest-api',
			'url'     => sanitize_text_field( $source->url ),
			'method'  => sanitize_text_field( $source->method ),
			'auth'    => $source->auth,
			'slug'    => sanitize_text_field( $source->slug ),
		];
	}

	public static function validate_graphql_source( $source ) {
		$rest_api_source = self::validate_rest_api_source( $source );

		if ( is_wp_error( $rest_api_source ) ) {
			return $rest_api_source;
		}

		if ( empty( $source->query ) ) {
			return new WP_Error( 'missing_query', __( 'Missing query.', 'remote-data-blocks' ) );
		}

		return (object) array_merge(
			(array) $rest_api_source,
			[
				'service' => 'graphql',
				'query'   => sanitize_textarea_field( $source->query ),
			]
		);
	}

	public static function validate_source( $source ) {
		if ( ! is_object( $source ) ) {
			return new WP_Error( 'invalid_data_source', __( 'Invalid data source.', 'remote-data-blocks' ) );
		}

		if ( empty( $source->uuid ) ) {
			return new WP_Error( 'missing_uuid', __( 'Missing UUID.', 'remote-data-blocks' ) );
		}


		if ( ! wp_is_uuid( $source->uuid ) ) {
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
			case 'google-sheets':
				return self::validate_google_sheets_source( $source );
			case 'rest-api':
				return self::validate_rest_api_source( $source );
			case 'graphql':
				return self::validate_graphql_source( $source );
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
			return array_values( array_filter($data_sources, function ( $config ) use ( $service ) {
				return $config->service === $service;
			} ) );
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
}
