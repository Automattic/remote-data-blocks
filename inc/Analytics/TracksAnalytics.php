<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Analytics;

defined( 'ABSPATH' ) || exit();

use Automattic\VIP\Telemetry\Tracks;
use RemoteDataBlocks\Editor\BlockManagement\ConfigStore;

/**
 * Tracks Analytics class.
 */
class TracksAnalytics {
	/**
	 * The tracks instance.
	 *
	 * @psalm-suppress UndefinedClass
	 */
	private static Tracks $tracks;

	/**
	 * Initialize the analytics.
	 */
	public static function init(): void {
		if ( ! class_exists( 'Automattic\VIP\Telemetry\Tracks' ) ) {
			return;
		}

		$is_enabled_via_filter = apply_filters( 'remote_data_blocks_enable_tracks_analytics', false );

		if ( self::is_wpvip_site() || $is_enabled_via_filter ) {
			self::$tracks = new Tracks(
				'',
				[
					'plugin_version'   => constant( 'REMOTE_DATA_BLOCKS__PLUGIN_VERSION' ),
					'is_multisite'     => is_multisite(),
					'wp_version'       => get_bloginfo( 'version' ),
					'hosting_provider' => self::get_hosting_provider(),
				]
			);

			self::setup_tracking_via_hooks();
		}
	}

	/**
	 * Setup tracking via hooks.
	 */
	public static function setup_tracking_via_hooks(): void {
		// WordPress Dashboard Hooks.
		add_action( 'activated_plugin', [ __CLASS__, 'track_plugin_activation' ] );
		add_action( 'deactivated_plugin', [ __CLASS__, 'track_plugin_deactivation' ] );
		add_action( 'save_post', [ __CLASS__, 'track_remote_data_blocks_usage' ], 10, 2 );
	}

	/**
	 * Activation hook.
	 *
	 * @param string $plugin_path Path of the plugin that was activated.
	 */
	public static function track_plugin_activation( string $plugin_path ): void {
		if ( plugin_basename( __FILE__ ) !== $plugin_path ) {
			return;
		}

		self::record_event( 'remotedatablocks_plugin_toggle', [ 'action' => 'activate' ] );
	}

	/**
	 * Deactivation hook.
	 *
	 * @param string $plugin_path Path of the plugin that was deactivated.
	 */
	public static function track_plugin_deactivation( string $plugin_path ): void {
		if ( plugin_basename( __FILE__ ) !== $plugin_path ) {
			return;
		}

		self::record_event( 'remotedatablocks_plugin_toggle', [ 'action' => 'deactivate' ] );
	}

	/**
	 * Track usage of Remote Data Blocks.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post Post object.
	 */
	public static function track_remote_data_blocks_usage( int $post_id, \WP_Post $post ): void {
		// Ensure this is not an auto-save or revision.
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// Regular expression to match all remote data blocks present in the post content.
		$reg_exp = '/<!--\s{1}wp:remote-data-blocks\/([^\s]+)\s/';
		preg_match_all( $reg_exp, $post->post_content, $matches );
		if ( count( $matches ) === 0 ) {
			return;
		}

		// Get data source and track usage.
		$track_props = [
			'post_status' => $post->post_status,
			'post_type'   => $post->post_type,
		];
		foreach ( $matches[1] as $match ) {
			$block_name  = 'remote-data-blocks/' . $match;
			$data_source = ConfigStore::get_data_source( $block_name );

			if ( ! $data_source ) {
				continue;
			}

			// Calculate stats of remote data blocks for tracking.
			$data_source_prop                              = $data_source . '_data_source_count';
			$track_props[ $data_source_prop ]              = ( $track_props[ $data_source_prop ] ?? 0 ) + 1;
			$track_props['remote_data_blocks_total_count'] = ( $track_props['remote_data_blocks_total_count'] ?? 0 ) + 1;
		}

		self::record_event( 'remotedatablocks_blocks_usage_stats', $track_props );
	}

	/**
	 * Track an event.
	 *
	 * @param string $event_name The name of the event.
	 * @param array  $props      The properties to send with the event.
	 */
	public static function record_event( string $event_name, array $props ): void {
		if ( ! isset( self::$tracks ) ) {
			return;
		}

		/** @psalm-suppress UndefinedClass */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort
		self::$tracks->record_event( $event_name, $props );
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
