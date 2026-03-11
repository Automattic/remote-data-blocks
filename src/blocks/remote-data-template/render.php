<?php declare(strict_types = 1);

use RemoteDataBlocks\Editor\DataBinding\BlockBindings;

// Global variables provided by WordPress for block rendering:
// $attributes (array): The block attributes.
// $content (string): The block default content.
// $block (WP_Block): The block instance.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo BlockBindings::render_remote_data_template_block( $attributes, $content, $block );
