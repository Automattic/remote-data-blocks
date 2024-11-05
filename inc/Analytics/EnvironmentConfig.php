<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Analytics;

defined( 'ABSPATH' ) || exit();

use Automattic\VIP\Telemetry\Tracks;
use function Automattic\VIP\Telemetry\Tracks\get_tracks_core_properties;

/**
 * Class for environment configuration.
 *
 * This class abstracts WordPress-specific functions for easy mocking.
 */
class EnvironmentConfig {
	/**
	 * Tracks analytics core properties.
	 * 
	 * This is set by the Tracks library available in MU Plugins.
	 *
	 * @var array<string, mixed>
	 */
	private array $tracks_core_props = [];

	public function __construct() {
		if ( function_exists( 'Automattic\VIP\Telemetry\Tracks\get_tracks_core_properties' ) ) {
			add_action( 'init', function (): void {
				/** @psalm-suppress UndefinedFunction */
				$this->tracks_core_props = get_tracks_core_properties();
			} );
		}
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

	public function is_wpvip_site(): bool {
		if ( ! isset( $this->tracks_core_props['hosting_provider'] ) ) {
			return false;
		}

		return 'wpvip' === $this->tracks_core_props['hosting_provider'];
	}

	public function is_local_env(): bool {
		if ( ! isset( $this->tracks_core_props['vipgo_env'] ) ) {
			return false;
		}

		return 'local' === $this->tracks_core_props['vipgo_env'];
	}

	public function is_remote_data_blocks_plugin( string|null $plugin_path ): bool {
		return 'remote-data-blocks/remote-data-blocks.php' === $plugin_path;
	}

	public function should_track_post_having_remote_data_blocks( int $post_id ): bool {
		// Ensure this is not an auto-save or revision.
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the core properties to be sent with each event.
	 */
	public function get_tracks_core_properties(): array {
		return $this->tracks_core_props;
	}
}
