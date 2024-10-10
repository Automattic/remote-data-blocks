<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Analytics;

defined( 'ABSPATH' ) || exit();

use Automattic\VIP\Telemetry\Tracks;
use RemoteDataBlocks\Editor\BlockManagement\ConfigStore;

/**
 * Class to implement Tracks Analytics in the codebase.
 */
class TracksAnalytics {
	/**
	 * The tracks instance (not using Tracks as type because it is not present in the codebase).
	 */
	private object|null $instance = null;

	public function __construct() {
		$tracks_class = $this->get_tracks_lib_class();
		if ( ! $tracks_class ) {
			return;
		}

		if ( $this->is_wpvip_site() || $this->is_enabled_via_filter() ) {
			$this->instance = new $tracks_class(
				'',
				[
					'plugin_version'   => defined( 'REMOTE_DATA_BLOCKS__PLUGIN_VERSION' ) ? constant( 'REMOTE_DATA_BLOCKS__PLUGIN_VERSION' ) : '',
					'is_multisite'     => is_multisite(),
					'wp_version'       => get_bloginfo( 'version' ),
					'hosting_provider' => $this->get_hosting_provider(),
				]
			);

			$this->setup_tracking_via_hooks();
		}
	}

	private function get_hosting_provider(): string {
		if ( $this->is_wpvip_site() ) {
			return 'wpvip';
		}

		return 'other';
	}

	public function setup_tracking_via_hooks(): void {
		// WordPress Dashboard Hooks.
		add_action( 'activated_plugin', [ $this, 'track_plugin_activation' ] );
		add_action( 'deactivated_plugin', [ $this, 'track_plugin_deactivation' ] );
		add_action( 'save_post', [ $this, 'track_remote_data_blocks_usage' ], 10, 2 );
	}

	/**
	 * Activation hook.
	 *
	 * @param string $plugin_path Path of the plugin that was activated.
	 */
	public function track_plugin_activation( string $plugin_path ): void {
		if ( ! $this->is_remote_data_blocks_plugin( $plugin_path ) ) {
			return;
		}

		$this->record_event( 'remotedatablocks_plugin_toggle', [ 'action' => 'activate' ] );
	}

	/**
	 * Deactivation hook.
	 *
	 * @param string $plugin_path Path of the plugin that was deactivated.
	 */
	public function track_plugin_deactivation( string $plugin_path ): void {
		if ( ! $this->is_remote_data_blocks_plugin( $plugin_path ) ) {
			return;
		}

		$this->record_event( 'remotedatablocks_plugin_toggle', [ 'action' => 'deactivate' ] );
	}

	/**
	 * Track usage of Remote Data Blocks.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post Post object.
	 */
	public function track_remote_data_blocks_usage( int $post_id, object $post ): void {
		if ( ! $this->should_track_blocks_usage( $post_id ) ) {
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

		$this->record_event( 'remotedatablocks_blocks_usage_stats', $track_props );
	}

	/**
	 * Track an event.
	 *
	 * @param string $event_name The name of the event.
	 * @param array  $props      The properties to send with the event.
	 *
	 * @return bool True if the event was recorded, false otherwise.
	 */
	public function record_event( string $event_name, array $props ): bool {
		if ( ! isset( $this->instance ) ) {
			return false;
		}

		$this->instance->record_event( $event_name, $props );
		return true;
	}

	public function is_enabled_via_filter(): bool {
		return apply_filters( 'remote_data_blocks_enable_tracks_analytics', false ) ?? false;
	}

	/**
	 * Check if the plugin is Remote Data Blocks.
	 */
	public function is_remote_data_blocks_plugin( string $plugin_path ): bool {
		return plugin_basename( __FILE__ ) === $plugin_path;
	}

	public function get_tracks_lib_class(): ?string {
		if ( ! class_exists( 'Automattic\VIP\Telemetry\Tracks' ) ) {
			return null;
		}

		return Tracks::class;
	}

	public function is_wpvip_site(): bool {
		return defined( 'WPCOM_IS_VIP_ENV' ) && constant( 'WPCOM_IS_VIP_ENV' ) === true
			&& defined( 'WPCOM_SANDBOXED' ) && constant( 'WPCOM_SANDBOXED' ) === false;
	}

	public function should_track_blocks_usage( int $post_id ): bool {
		// Ensure this is not an auto-save or revision.
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return false;
		}

		return true;
	}

	public function get_instance(): ?object {
		return isset( $this->instance ) ? $this->instance : null;
	}
}
