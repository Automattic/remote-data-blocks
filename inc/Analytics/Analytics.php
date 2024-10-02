<?php
/**
 * Analytics for the Remote Data Blocks plugin.
 * 
 * @package remote-data-blocks
 */

namespace RemoteDataBlocks\Analytics;

defined( 'ABSPATH' ) || exit();

/**
 * Analytics class.
 */
class Analytics {
	/**
	 * Checks if tracking is enabled.
	 */
	public static function is_enabled(): bool {
		$is_enabled_via_filter = apply_filters( 'remote_data_blocks_enable_analytics', false );

		return self::is_wpvip_site() || $is_enabled_via_filter;
	}

	/**
	 * Check if the site is a WPVIP site.
	 */
	private static function is_wpvip_site() {
		return defined( 'WPCOM_IS_VIP_ENV' ) && constant( 'WPCOM_IS_VIP_ENV' ) === true
			&& defined( 'WPCOM_SANDBOXED' ) && constant( 'WPCOM_SANDBOXED' ) === false;
	}
}
