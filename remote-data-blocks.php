<?php declare(strict_types = 1);

/**
 * Plugin Name: Remote Data Blocks
 * Plugin URI: https://remotedatablocks.com
 * Description: Integrate external data sources into WordPress blocks, enabling dynamic content from APIs and databases within the block editor and within your content.
 * Author: Automattic
 * Author URI: https://automattic.com
 * Text Domain: remote-data-blocks
 * Version: 1.4.1
 * Requires at least: 6.7
 * Requires PHP: 8.1
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace RemoteDataBlocks;

defined( 'ABSPATH' ) || exit();

// Check if the plugin is already loaded, if so, return early to prevent duplicate plugin instances.
if ( defined( 'REMOTE_DATA_BLOCKS__LOADED' ) ) {
	return;
}

define( 'REMOTE_DATA_BLOCKS__LOADED', true );
define( 'REMOTE_DATA_BLOCKS__PLUGIN_ROOT', __FILE__ );
define( 'REMOTE_DATA_BLOCKS__PLUGIN_DIRECTORY', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'REMOTE_DATA_BLOCKS__PLUGIN_VERSION', '1.4.1' );

define( 'REMOTE_DATA_BLOCKS__REST_NAMESPACE', 'remote-data-blocks/v1' );

// Autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Other editor modifications
Editor\AdminNotices\AdminNotices::init();
Editor\DataBinding\BlockBindings::init();
Editor\DataBinding\InlineBindings::init();
Editor\DataBinding\Pagination::init();
Editor\BlockManagement\BlockRegistration::init();
Editor\BlockManagement\ConfigRegistry::init();
Editor\PatternEditor\PatternEditor::init();

// Telemetry
Telemetry\Telemetry::init( __FILE__ );

// Example API
ExampleApi\ExampleApi::init();

// Load Settings Page
PluginSettings\PluginSettings::init();

// Integrations
Integrations\Airtable\AirtableIntegration::init();
Integrations\Google\Sheets\GoogleSheetsIntegration::init();
Integrations\Shopify\ShopifyIntegration::init();
Integrations\VipBlockDataApi\VipBlockDataApi::init();

// REST endpoints
REST\RemoteDataController::init();

// QueryMonitor panel
Logging\QueryMonitor\QueryMonitor::init();

// Fire action to indicate that the plugin is loaded
do_action( 'remote_data_blocks_loaded' );

// Plugin developers: If you need to register additional code for testing, you
// can do so here, e.g.:
// require_once __DIR__ . '/example/blocks/art-block/art-block.php';
// require_once __DIR__ . '/example/blocks/book-block/book-block.php';
// require_once __DIR__ . '/example/blocks/github-markdown-block/github-markdown-block.php';
// require_once __DIR__ . '/example/blocks/shopify-mock-store-block/shopify-mock-store-block.php';
// require_once __DIR__ . '/example/blocks/weather-block/weather-block.php';
// require_once __DIR__ . '/example/blocks/zip-code-block/zip-code-block.php';



$data_source = [
	'display_name' => 'JSONPlaceholder API',
	'endpoint' => 'https://jsonplaceholder.typicode.com/posts/1',
];

$render_query = [
	'display_name' => 'Pattern Test Query',
	'data_source' => $data_source,
	'output_schema' => [
		'type' => [
			'title' => [ 'name' => 'Title', 'type' => 'string' ],
			'body' => [ 'name' => 'Body', 'type' => 'string' ],
		],
	],
];

register_remote_data_block( [
    'title' => 'Pattern Test',
    'icon' => 'list-view',
    'render_query' => [
        'query' => $render_query,
    ],
    'patterns' => [
        [
            'title' => 'Simple',
            'html' => '<!-- wp:heading {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"block":"remote-data-blocks/pattern-test","field":"title"}}}}} --><h2></h2><!-- /wp:heading -->',
        ],
        [
            'title' => 'With Background',
            'html' => '<!-- wp:group {"backgroundColor":"pale-cyan-blue"} --><div class="wp-block-group has-pale-cyan-blue-background-color"><!-- wp:heading {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"block":"remote-data-blocks/pattern-test","field":"title"}}}}} --><h2></h2><!-- /wp:heading --></div><!-- /wp:group -->',
        ],
        [
            'title' => 'With Body',
            'html' => '<!-- wp:group --><div><!-- wp:heading {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"block":"remote-data-blocks/pattern-test","field":"title"}}}}} --><h2></h2><!-- /wp:heading --><!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"block":"remote-data-blocks/pattern-test","field":"body"}}}}} --><p></p><!-- /wp:paragraph --></div><!-- /wp:group -->',
        ],
    ],
] );