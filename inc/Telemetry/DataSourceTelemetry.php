<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Telemetry;

use RemoteDataBlocks\Telemetry\TracksTelemetry;
use RemoteDataBlocks\Config\DataSource\DataSourceConfigManager;

defined( 'ABSPATH' ) || exit();

class DataSourceTelemetry {
	const DATA_SOURCE_INTERACTION_EVENT_NAME = 'data_source_interaction';
	const DATA_SOURCE_VIEW_EVENT_NAME = 'view_data_sources';
	const DATA_SOURCE_VIEW_TRACK_TRANSIENT_KEY = 'remotedatablocks_view_data_sources_tracked';

	private static function get_interaction_track_props( array $config ): array {
		$props = [];

		if ( 'generic-http' === $config['service'] ) {
			$auth = $config['service_config']['auth'] ?? [];
			$props['authentication_type'] = $auth['type'] ?? '';
			$props['api_key_location'] = $auth['addTo'] ?? '';
		}

		return $props;
	}

	private static function track_interaction( array $config, string $action ): void {
		TracksTelemetry::record_event( self::DATA_SOURCE_INTERACTION_EVENT_NAME, array_merge( [
			'data_source_type' => $config['service'],
			'action' => $action,
		], self::get_interaction_track_props( $config ) ) );
	}

	public static function track_add( array $config ): void {
		self::track_interaction( $config, 'add' );
	}

	public static function track_update( array $config ): void {
		self::track_interaction( $config, 'update' );
	}

	public static function track_delete( array $config ): void {
		self::track_interaction( $config, 'delete' );
	}

	public static function track_view( array $configs ): void {
		/**
		 * Tracks Telemetry. Only once per day to reduce noise.
		 */
		if ( ! get_transient( self::DATA_SOURCE_VIEW_TRACK_TRANSIENT_KEY ) ) {
			$code_configured_count = count( array_filter(
				$configs,
				function ( $config ) {
					return DataSourceConfigManager::CONFIG_SOURCE_CODE === $config['config_source'];
				}
			) );
			$storage_configured_count = count( array_filter(
				$configs,
				function ( $config ) {
					return DataSourceConfigManager::CONFIG_SOURCE_STORAGE === $config['config_source'];
				}
			) );

			TracksTelemetry::record_event( self::DATA_SOURCE_VIEW_EVENT_NAME, [
				'total_data_sources_count' => count( $configs ),
				'code_configured_data_sources_count' => $code_configured_count,
				'ui_configured_data_sources_count' => $storage_configured_count,
			] );

			set_transient( self::DATA_SOURCE_VIEW_TRACK_TRANSIENT_KEY, true, DAY_IN_SECONDS );
		}
	}
}
