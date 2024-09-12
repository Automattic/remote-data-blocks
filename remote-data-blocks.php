<?php
/**
 * Plugin Name: Remote Data Blocks
 * Plugin URI: https://github.com/Automattic/remote-data-blocks
 * Description: Integrate external data sources into WordPress blocks, enabling dynamic content from APIs and databases within the block editor and within your content.
 * Author: WPVIP
 * Text Domain: remote-data-blocks
 * Version: 0.1.0
 * Requires at least: 6.6
 * Requires PHP: 8.1
 *
 * @package remote-data-blocks
 */

namespace RemoteDataBlocks;

defined( 'ABSPATH' ) || exit();

define( 'REMOTE_DATA_BLOCKS__PLUGIN_ROOT', __FILE__ );
define( 'REMOTE_DATA_BLOCKS__PLUGIN_DIRECTORY', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'REMOTE_DATA_BLOCKS__PLUGIN_VERSION', '0.1.0' );

define( 'REMOTE_DATA_BLOCKS__REST_NAMESPACE', 'remote-data-blocks/v1' );

// Datasource services
define( 'REMOTE_DATA_BLOCKS_AIRTABLE_SERVICE', 'airtable' );
define( 'REMOTE_DATA_BLOCKS_GITHUB_SERVICE', 'github' );
define( 'REMOTE_DATA_BLOCKS_SHOPIFY_SERVICE', 'shopify' );

// Autoloader
require_once __DIR__ . '/inc/autoloader.php';
require_once __DIR__ . '/vendor/autoload.php';

// Other editor modifications
Editor\AdminNotices::init();
Editor\BlockBindings::init();
Editor\BlockRegistration::init();
Editor\ConfigurationLoader::init();
Editor\FieldShortcode::init();
Editor\QueryOverrides::init();
Editor\PatternEditor::init();

// Load Settings Page
PluginSettings::init();

// Integrations
Integrations\ShopifyIntegration::init();
Integrations\VipBlockDataApi::init();

// REST endpoints
REST\RemoteData::init();
