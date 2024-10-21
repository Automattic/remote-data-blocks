<?php declare(strict_types = 1);

/**
 * Plugin Name: Remote Data Blocks Examples
 * Plugin URI: https://github.com/Automattic/remote-data-blocks/blob/trunk/example
 * Description: Example implementations and usage demonstrations for the Remote Data Blocks plugin, showcasing integration with various external data sources.
 * Author: WPVIP
 * Text Domain: remote-data-blocks
 * Version: 0.1.0
 * Requires at least: 6.6
 * Requires PHP: 8.1
 * Requires plugins: remote-data-blocks
 */

namespace RemoteDataBlocks\Example;

use function add_action;
use function apply_filters;

defined( 'ABSPATH' ) || exit();

function plugin_dependency_notice() {
	?>
<div class="notice notice-error">
		<p><strong>Remote Data Blocks Example Code</strong> requires <strong>Remote Data Blocks</strong> to be installed and active.</p>
</div>
		<?php
}

function load_only_if_parent_plugin_is_active() {
	if ( ! defined( 'REMOTE_DATA_BLOCKS__PLUGIN_VERSION' ) ) {
		add_action( 'admin_notices', __NAMESPACE__ . '\\plugin_dependency_notice', 10, 0 );
		return;
	}

	require_once __DIR__ . '/airtable/elden-ring-map/register.php';
	require_once __DIR__ . '/github/remote-data-blocks/register.php';
	require_once __DIR__ . '/rest-api/art-institute/register.php';
	require_once __DIR__ . '/rest-api/zip-code/register.php';
	require_once __DIR__ . '/shopify/register.php';
	require_once __DIR__ . '/google-sheets/westeros-houses/register.php';
	require_once __DIR__ . '/tulum-workshop-demo/register.php';
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\load_only_if_parent_plugin_is_active', 10, 0 );

/**
 * Get the access token for a specific example.
 *
 * @param string $example_name The name of the example.
 * @return string The access token.
 */
function get_access_token( string $example_name ): string {
	$supported_tokens = [
		'airtable_elden_ring',
		'airtable_events',
		'shopify',
		'google_sheets_westeros_houses',
	];

	if ( ! in_array( $example_name, $supported_tokens, true ) ) {
		return '';
	}

	$constant_name = strtoupper( 'REMOTE_DATA_BLOCKS_EXAMPLE_' . $example_name . '_ACCESS_TOKEN' );
	$default_value = defined( $constant_name ) ? constant( $constant_name ) : '';

	/**
	 * Filters the access token for a specific example.
	 *
	 * @param string $default_value The default value for the access token.
	 * @param string $example_name  The name of the example.
	 * @return string The access token. The result will be explicitly cast to a string prior to use.
	 */
	return (string) apply_filters( 'remote_data_blocks_example_token', $default_value, $example_name );
}
