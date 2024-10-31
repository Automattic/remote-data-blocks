<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Analytics;

defined( 'ABSPATH' ) || exit();

use Automattic\VIP\Telemetry\Tracks;
use function Automattic\VIP\Telemetry\Tracks\get_base_properties_of_track_event;

class EnvironmentConfig {
	public function is_wpvip_site(): bool {
		return defined( 'WPCOM_IS_VIP_ENV' ) && constant( 'WPCOM_IS_VIP_ENV' ) === true
			&& defined( 'WPCOM_SANDBOXED' ) && constant( 'WPCOM_SANDBOXED' ) === false;
	}

	public function get_hosting_provider(): string {
		if ( $this->is_wpvip_site() ) {
			return 'wpvip';
		}

		return 'other';
	}

	public function is_enabled_via_filter(): bool {
		return apply_filters( 'remote_data_blocks_enable_tracks_analytics', false ) ?? false;
	}

	public function get_tracks_lib_class(): ?string {
		if ( ! class_exists( 'Automattic\VIP\Telemetry\Tracks' ) ) {
			return null;
		}

		return Tracks::class;
	}

	public function is_local_env(): bool {
		$vip_base_props = [];

		if ( function_exists( 'Automattic\VIP\Telemetry\Tracks\get_base_properties_of_track_event' ) ) {
			$vip_base_props = get_base_properties_of_track_event();
		}

		if ( ! isset( $vip_base_props['vipgo_env'] ) ) {
			return false;
		}

		return 'local' === $vip_base_props['vipgo_env'];
	}

	public function is_remote_data_blocks_plugin( string|null $plugin_path ): bool {
		return plugin_basename( __FILE__ ) === $plugin_path;
	}

	public function should_track_post_having_remote_data_blocks( int $post_id ): bool {
		// Ensure this is not an auto-save or revision.
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return false;
		}

		return true;
	}
}
