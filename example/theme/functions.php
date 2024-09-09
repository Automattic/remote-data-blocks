<?php

namespace RemoteDataBlocks\Example\Theme;

use function add_action;
use function get_stylesheet_directory_uri;
use function wp_enqueue_style;
use function wp_get_theme;

defined( 'ABSPATH' ) || exit();

/**
 * Enqueue the child theme stylesheet.
 */
function remote_data_blocks_example_theme_enqueue_styles() {
	wp_enqueue_style(
		'remote-data-blocks-example-theme-style',
		get_stylesheet_directory_uri() . '/style.css',
		[],
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\remote_data_blocks_example_theme_enqueue_styles', 15, 0 );
