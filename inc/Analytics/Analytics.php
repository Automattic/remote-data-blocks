<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Analytics;

defined( 'ABSPATH' ) || exit();

use Automattic\VIP\Telemetry\Tracks;

/**
 * Analytics class.
 */
class Analytics {
	/**
	 * The tracks instance.
	 *
	 * @var Tracks
	 */
	private static $tracks;

	/**
	 * Initialize the analytics.
	 */
	public static function init(): void {
		if ( class_exists( 'Automattic\VIP\Telemetry\Tracks' ) ) {
			self::$tracks = new Tracks(
				'',
				[
					'plugin_version'   => constant( 'REMOTE_DATA_BLOCKS__PLUGIN_VERSION' ),
					'is_multisite'     => is_multisite(),
					'wp_version'       => get_bloginfo( 'version' ),
					'hosting_provider' => self::get_hosting_provider(),
				]
			);
		}
	}

	/**
	 * Track an event.
	 *
	 * @param string $event_name The name of the event.
	 * @param array  $props      The properties to send with the event.
	 */
	public static function track_event( string $event_name, array $props ): void {
		if ( ! self::is_enabled() ) {
			return;
		}

		self::$tracks->record_event( $event_name, $props );
	}

	/**
	 * Checks if tracking is enabled.
	 */
	private static function is_enabled(): bool {
		if ( ! isset( self::$tracks ) ) {
			return false;
		}

		$is_enabled_via_filter = apply_filters( 'remote_data_blocks_enable_analytics', false );

		return self::is_wpvip_site() || $is_enabled_via_filter;
	}

	/**
	 * Check if the site is a WPVIP site.
	 */
	private static function is_wpvip_site(): bool {
		return defined( 'WPCOM_IS_VIP_ENV' ) && constant( 'WPCOM_IS_VIP_ENV' ) === true
			&& defined( 'WPCOM_SANDBOXED' ) && constant( 'WPCOM_SANDBOXED' ) === false;
	}

	/**
	 * Get the hosting provider.
	 */
	private static function get_hosting_provider(): string {
		if ( self::is_wpvip_site() ) {
			return 'wpvip';
		}

		return 'other';
	}
}
