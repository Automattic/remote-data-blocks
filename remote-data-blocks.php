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
function rdb_plugin_activation( string $plugin_path ): void {
	if ( $plugin_path !== plugin_basename( __FILE__ ) ) {
		return;
	}

	Analytics::track_event( 'remotedatablocks_plugin_toggle', [ 'action' => 'activate' ] );
}
add_action( 'activated_plugin', __NAMESPACE__ . '\\rdb_plugin_activation' );

/**
 * Deactivation hook.
 *
 * @param string $plugin_path Path of the plugin that was deactivated.
 */
function rdb_plugin_deactivation( string $plugin_path ): void {
	if ( $plugin_path !== plugin_basename( __FILE__ ) ) {
		return;
	}

	Analytics::track_event( 'remotedatablocks_plugin_toggle', [ 'action' => 'deactivate' ] );
}
add_action( 'deactivated_plugin', __NAMESPACE__ . '\\rdb_plugin_deactivation' );
