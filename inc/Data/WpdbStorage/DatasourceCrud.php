<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Data\WpdbStorage;

use RemoteDataBlocks\Config\ArraySerializableInterface;
use RemoteDataBlocks\Config\Datasource\DatasourceInterface;
use RemoteDataBlocks\Config\Datasource\HttpDatasourceInterface;
use RemoteDataBlocks\Data\DataSourceProviderInterface;
use RemoteDataBlocks\Data\DataSourceWriterInterface;
use RemoteDataBlocks\Logging\LoggerManager;
use WP_Error;

use const RemoteDataBlocks\REMOTE_DATA_BLOCKS__DATASOURCE_CLASSMAP;

class DatasourceCrud implements DataSourceProviderInterface, DataSourceWriterInterface {
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
	
		$slug_exists = self::get( $slug );

		if ( ! empty( $slug_exists ) ) {
			return new WP_Error( 'slug_already_taken', __( 'Slug already taken.', 'remote-data-blocks' ) );
		}

		return true;
	}

	public static function register_new_data_source( array $settings, ?ArraySerializableInterface $datasource = null ): HttpDatasourceInterface|WP_Error {
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

	public static function get_data_sources(): array {
		$data_sources = [];

		foreach ( self::get_config() as $data_source_config ) {
			$datasource = self::resolve_datasource( $data_source_config );
			
			if ( is_wp_error( $datasource ) ) {
				LoggerManager::instance()->error( $datasource->get_error_message() );
				continue;
			}

			$data_sources[] = $datasource;
		}

		return $data_sources;
	}

	public static function get_item_by_uuid( array $data_sources, string $uuid ): array|false {
		return $data_sources[ $uuid ] ?? false;
	}

	public static function update_item_by_uuid( string $uuid, array $new_item, ?ArraySerializableInterface $datasource = null ): HttpDatasourceInterface|WP_Error {
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
		
		return $datasource;
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
	public static function get( string $slug ): ?DatasourceInterface {
		$data_sources = self::get_data_sources();
		foreach ( $data_sources as $source ) {
			if ( $source->get_slug() === $slug ) {
				return $source;
			}
		}
		return null;
	}

	public static function find_by( array $criteria ): array {
		$data_sources = self::get_data_sources();
		$results      = [];

		foreach ( $data_sources as $data_source ) {
			$match = true;
			foreach ( $criteria as $key => $value ) {
				if ( ! isset( $data_source[ $key ] ) || $data_source[ $key ] !== $value ) {
					$match = false;
					break;
				}
			}
			if ( $match ) {
				$results[] = $data_source;
			}
		}

		return $results;	
	}

	public function insert( DatasourceInterface $datasource ): bool {
		$data_sources                            = self::get_data_sources();
		$data_sources[ $datasource->get_slug() ] = $datasource->to_array();
		return update_option( self::CONFIG_OPTION_NAME, $data_sources );
	}

	public function update( DatasourceInterface $datasource ): bool {
		$result = self::save_datasource( $datasource, self::get_data_sources() );
		return ! is_wp_error( $result );
	}   

	public function delete( DatasourceInterface $datasource ): bool {
		$result = self::delete_item_by_uuid( $datasource->get_uuid() );
		return ! is_wp_error( $result );
	}

	public static function is_responsible_for_data_source( DatasourceInterface $datasource ): bool {
		if ( ! $datasource instanceof ArraySerializableInterface ) {
			return false;
		}

		$config = $datasource->to_array();
		return isset( $config['__metadata']['writer'] ) && $config['__metadata']['writer'] === self::class;
	}

	private static function save_datasource( ArraySerializableInterface $datasource, array $datasource_configs ): bool {
		$config = $datasource->to_array();
		
		if ( ! isset( $config['__metadata'] ) ) {
			$config['__metadata'] = [ 'writer' => self::class ];
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
