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

// Integrations
Integrations\VipBlockDataApi::init();

// REST endpoints
REST\RemoteData::init();

// Load Settings Page
PluginSettings::init();
