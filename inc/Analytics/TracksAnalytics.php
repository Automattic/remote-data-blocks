<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Analytics;

defined( 'ABSPATH' ) || exit();

use RemoteDataBlocks\Editor\BlockManagement\ConfigStore;

/**
 * Class to implement Tracks Analytics in the codebase.
 */
class TracksAnalytics {
	/**
	 * The tracks instance (not using Tracks as type because it is not present in the codebase).
	 */
	private static object|null $instance = null;

	/**
	 * Environment configuration.
	 *
	 */
	private static ?EnvironmentConfig $env_config = null;

	public static function init( EnvironmentConfig $env_config ): void {
		self::$env_config = $env_config;

		$tracks_class = self::$env_config->get_tracks_lib_class();
		if ( ! $tracks_class ) {
			return;
		}

		// Do not track on local environment.
		if ( self::$env_config->is_local_env() ) {
			return;
		}

		if ( self::$env_config->is_wpvip_site() || self::$env_config->is_enabled_via_filter() ) {
			self::$instance = new $tracks_class(
				'',
				[
					'plugin_version'   => defined( 'REMOTE_DATA_BLOCKS__PLUGIN_VERSION' ) ? constant( 'REMOTE_DATA_BLOCKS__PLUGIN_VERSION' ) : '',
					'is_multisite'     => is_multisite(),
					'wp_version'       => get_bloginfo( 'version' ),
					'hosting_provider' => self::$env_config->get_hosting_provider(),
				]
			);

			self::setup_tracking_via_hooks();
		}
	}

	private static function setup_tracking_via_hooks(): void {
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
		if ( ! self::$env_config->is_remote_data_blocks_plugin( $plugin_path ) ) {
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
		if ( ! self::$env_config->is_remote_data_blocks_plugin( $plugin_path ) ) {
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
	public static function track_remote_data_blocks_usage( int $post_id, object $post ): void {
		if ( ! self::$env_config->should_track_post_having_remote_data_blocks( $post_id ) ) {
			return;
		}

		$post_status = $post->post_status;
		if ( 'publish' !== $post_status ) {
			return;
		}

		// Regular expression to match all remote data blocks present in the post content.
		$reg_exp = '/<!--\s{1}wp:remote-data-blocks\/([^\s]+)\s/';
		preg_match_all( $reg_exp, $post->post_content, $matches );
		if ( count( $matches[1] ) === 0 ) {
			return;
		}

		// Get data source and track usage.
		$track_props = [
			'post_status' => $post_status,
			'post_type'   => $post->post_type,
		];
		foreach ( $matches[1] as $match ) {
			$data_source = ConfigStore::get_data_source( 'remote-data-blocks/' . $match );
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
	 *
	 * @return bool True if the event was recorded, false otherwise.
	 */
	public static function record_event( string $event_name, array $props ): bool {
		if ( ! isset( self::$instance ) ) {
			return false;
		}

		self::$instance->record_event( $event_name, $props );
		return true;
	}

	public static function get_instance(): ?object {
		return self::$instance;
	}

	public static function get_env_config(): ?object {
		return self::$env_config;
	}

	public static function reset(): void {
		self::$instance   = null;
		self::$env_config = null;
	}
}
