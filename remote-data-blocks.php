<?php declare(strict_types = 1);

/**
 * Plugin Name: Remote Data Blocks
 * Plugin URI: https://remotedatablocks.com
 * Description: Integrate external data sources into WordPress blocks, enabling dynamic content from APIs and databases within the block editor and within your content.
 * Author: WPVIP
 * Author URI: https://wpvip.com
 * Text Domain: remote-data-blocks
 * Version: 0.2.1
 * Requires at least: 6.6
 * Requires PHP: 8.1
 */

namespace RemoteDataBlocks;

use RemoteDataBlocks\Analytics\Analytics;
use RemoteDataBlocks\Editor\BlockManagement\ConfigStore;

defined( 'ABSPATH' ) || exit();

define( 'REMOTE_DATA_BLOCKS__PLUGIN_ROOT', __FILE__ );
define( 'REMOTE_DATA_BLOCKS__PLUGIN_DIRECTORY', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'REMOTE_DATA_BLOCKS__PLUGIN_VERSION', '0.2.1' );

define( 'REMOTE_DATA_BLOCKS__REST_NAMESPACE', 'remote-data-blocks/v1' );

// Autoloader.
require_once __DIR__ . '/vendor/autoload.php';

// Other editor modifications.
Editor\AdminNotices\AdminNotices::init();
Editor\DataBinding\BlockBindings::init();
Editor\DataBinding\FieldShortcode::init();
Editor\DataBinding\QueryOverrides::init();
Editor\BlockManagement\BlockRegistration::init();
Editor\BlockManagement\ConfigRegistry::init();
Editor\PatternEditor\PatternEditor::init();

// Analytics.
Analytics::init();

// Example API.
ExampleApi\ExampleApi::init();

// Load Settings Page.
PluginSettings\PluginSettings::init();

// Integrations.
Integrations\Airtable\AirtableIntegration::init();
Integrations\Shopify\ShopifyIntegration::init();
Integrations\VipBlockDataApi\VipBlockDataApi::init();

// REST endpoints.
REST\RemoteDataController::init();


/**
 * Activation hook.
 *
 * @param string $plugin_path Path of the plugin that was activated.
 */
function rdb_track_plugin_activation( string $plugin_path ): void {
	if ( plugin_basename( __FILE__ ) !== $plugin_path ) {
		return;
	}

	Analytics::track_event( 'remotedatablocks_plugin_toggle', [ 'action' => 'activate' ] );
}
add_action( 'activated_plugin', __NAMESPACE__ . '\\rdb_track_plugin_activation' );

/**
 * Deactivation hook.
 *
 * @param string $plugin_path Path of the plugin that was deactivated.
 */
function rdb_track_plugin_deactivation( string $plugin_path ): void {
	if ( plugin_basename( __FILE__ ) !== $plugin_path ) {
		return;
	}

	Analytics::track_event( 'remotedatablocks_plugin_toggle', [ 'action' => 'deactivate' ] );
}
add_action( 'deactivated_plugin', __NAMESPACE__ . '\\rdb_track_plugin_deactivation' );

/**
 * Track usage of Remote Data Blocks.
 *
 * @param int      $post_id Post ID.
 * @param \WP_Post $post Post object.
 */
function rdb_track_remote_data_blocks_usage( int $post_id, \WP_Post $post ): void {
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

	Analytics::track_event( 'remotedatablocks_usage_stats', $track_props );
}
add_action( 'save_post', __NAMESPACE__ . '\\rdb_track_remote_data_blocks_usage', 10, 2 );
