<?php declare(strict_types = 1);

namespace RemoteDataBlocks\WpdbStorage;

use RemoteDataBlocks\Config\ArraySerializableInterface;
use RemoteDataBlocks\Config\DataSource\HttpDataSourceInterface;
use WP_Error;

use const RemoteDataBlocks\REMOTE_DATA_BLOCKS__DATA_SOURCE_CLASSMAP;

class DataSourceCrud {
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
			return array_values( array_filter($data_sources, function ( $config ) use ( $service ) {
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

	public static function update_item_by_uuid( string $uuid, array $new_item, ?ArraySerializableInterface $data_source = null ): HttpDataSourceInterface|WP_Error {
		$data_sources = self::get_data_sources();
		$item         = self::get_item_by_uuid( $data_sources, $uuid );
		if ( ! $item ) {
			return new WP_Error( 'data_source_not_found', __( 'Data source not found.', 'remote-data-blocks' ), [ 'status' => 404 ] );
		}

		$data_source = $data_source ?? self::resolve_data_source( array_merge( $item, $new_item ) );

		if ( is_wp_error( $data_source ) ) {
			return $data_source;
		}

		$result = self::save_data_source( $data_source, $data_sources );
		
		if ( true !== $result ) {
			return new WP_Error( 'failed_to_update_data_source', __( 'Failed to update data source.', 'remote-data-blocks' ) );
		}
		
		return $data_source;
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

	/**
	 * Get a data source by its slug.
	 * 
	 * The easiest way to think of the slug relative to data sources is it
	 * provides a developer-friendly "contract" for the data source. The developer
	 * can define the slug pre-emptively in their code and then customers can add it
	 * when configuring specific data sources later.
	 * 
	 * This function naively returns the first data source it finds. In theory, that
	 * should be the only one as we check for slug conflicts at present. In the future,
	 * it's be good to holistically improve how those interactions work
	 */
	public static function get_by_slug( string $slug ): array|false {
		$data_sources = self::get_data_sources();
		foreach ( $data_sources as $source ) {
			if ( $source['slug'] === $slug ) {
				return $source;
			}
		}
		return false;
	}

	private static function save_data_source( ArraySerializableInterface $data_source, array $data_source_configs ): bool {
		$config = $data_source->to_array();
		
		if ( ! isset( $config['__metadata'] ) ) {
			$config['__metadata'] = [];
		}

		$now = gmdate( 'Y-m-d H:i:s' );
		
		if ( ! isset( $config['__metadata']['created_at'] ) ) {
			$config['__metadata']['created_at'] = $now;
		}

		$config['__metadata']['updated_at']     = $now;
		$data_source_configs[ $config['uuid'] ] = $config;

		return update_option( self::CONFIG_OPTION_NAME, $data_source_configs );
	}

	private static function resolve_data_source( array $config ): HttpDataSourceInterface|WP_Error {
		if ( isset( REMOTE_DATA_BLOCKS__DATA_SOURCE_CLASSMAP[ $config['service'] ] ) ) {
			return REMOTE_DATA_BLOCKS__DATA_SOURCE_CLASSMAP[ $config['service'] ]::from_array( $config );
		}

		return new WP_Error( 'unsupported_data_source', __( 'DataSource class not found.', 'remote-data-blocks' ) );
	}
}
