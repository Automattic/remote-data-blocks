<?php declare(strict_types = 1);

/**
 * Plugin Name: Remote Data Blocks
 * Plugin URI: https://remotedatablocks.com
 * Description: Integrate external data sources into WordPress blocks, enabling dynamic content from APIs and databases within the block editor and within your content.
 * Author: WPVIP
 * Author URI: https://wpvip.com
 * Text Domain: remote-data-blocks
 * Version: 0.2.7
 * Requires at least: 6.7
 * Requires PHP: 8.1
 */

namespace RemoteDataBlocks;

defined( 'ABSPATH' ) || exit();

define( 'REMOTE_DATA_BLOCKS__PLUGIN_ROOT', __FILE__ );
define( 'REMOTE_DATA_BLOCKS__PLUGIN_DIRECTORY', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'REMOTE_DATA_BLOCKS__PLUGIN_VERSION', '0.2.7' );

define( 'REMOTE_DATA_BLOCKS__REST_NAMESPACE', 'remote-data-blocks/v1' );

// Autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Other editor modifications
Editor\AdminNotices\AdminNotices::init();
Editor\DataBinding\BlockBindings::init();
Editor\DataBinding\FieldShortcode::init();
Editor\DataBinding\QueryOverrides::init();
Editor\BlockManagement\BlockRegistration::init();
Editor\BlockManagement\ConfigRegistry::init();
Editor\PatternEditor\PatternEditor::init();

// Example API
ExampleApi\ExampleApi::init();

// Load Settings Page
PluginSettings\PluginSettings::init();

// Integrations
Integrations\Airtable\AirtableIntegration::init();
Integrations\Shopify\ShopifyIntegration::init();
Integrations\VipBlockDataApi\VipBlockDataApi::init();

// REST endpoints
REST\RemoteDataController::init();
