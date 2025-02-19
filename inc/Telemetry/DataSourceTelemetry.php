<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Telemetry;

use RemoteDataBlocks\Telemetry\TracksTelemetry;

defined( 'ABSPATH' ) || exit();

class DataSourceTelemetry {
	const DATA_SOURCE_INTERACTION_EVENT_NAME = 'data_source_interaction';

	private static function get_interaction_track_props( array $config ): array {
		$props = [];

		if ( REMOTE_DATA_BLOCKS_GENERIC_HTTP_SERVICE === $config['service'] ) {
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
}
