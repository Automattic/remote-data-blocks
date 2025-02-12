<?php declare(strict_types = 1);

namespace RemoteDataBlocks\MigrationService;

use RemoteDataBlocks\PluginSettings\PluginSettings;
use RemoteDataBlocks\WpdbStorage\DataSourceCrud;
use WP_Error;

use const RemoteDataBlocks\REMOTE_DATA_BLOCKS__DATA_SOURCE_CLASSMAP;

defined( 'ABSPATH' ) || exit();

class MigrationService {
	const MIGRATION_OPTION_NAME = 'remote_data_blocks_version';

	public static function init(): void {
		add_action( 'init', [ __CLASS__, 'migrate_data_source_configs' ] );
	}

	public static function migrate_data_source_configs(): WP_Error|bool {
		$migration_version = self::get_migration_version();

		if ( PluginSettings::get_version() === $migration_version ) {
			return true;
		}

		$data_sources = DataSourceCrud::get_configs();

		if ( is_wp_error( $data_sources ) ) {
			return $data_sources;
		}

		foreach ( $data_sources as $data_source ) {
			$data_source_class = REMOTE_DATA_BLOCKS__DATA_SOURCE_CLASSMAP[ $data_source['service'] ] ?? null;
			if ( null === $data_source_class ) {
				return new WP_Error( 'unsupported_data_source', __( 'Unsupported data source service', 'remote-data-blocks' ) );
			}

			$data_source_config = $data_source_class::from_array_for_migrations( $data_source );
			if ( is_wp_error( $data_source_config ) ) {
				return $data_source_config;
			}

			$migrated_service_config = $data_source_config->perform_migration( $data_source['service_config'] );
			if ( is_wp_error( $migrated_service_config ) ) {
				return $migrated_service_config;
			}

			// verify if the configs are the same so we can exit early
			if ( $data_source['service_config'] === $migrated_service_config ) {
				continue;
			}

			$updated_config = DataSourceCrud::update_config_by_uuid( $data_source['uuid'], $migrated_service_config );
			if ( is_wp_error( $updated_config ) ) {
				return $updated_config;
			}
		}

		self::set_migration_version();

		return true;
	}

	private static function set_migration_version(): void {
		update_option( self::MIGRATION_OPTION_NAME, PluginSettings::get_version() );
	}

	private static function get_migration_version(): string {
		return get_option( self::MIGRATION_OPTION_NAME, '' );
	}
}
