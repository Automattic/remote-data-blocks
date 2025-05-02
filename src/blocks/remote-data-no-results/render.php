<?php declare(strict_types = 1);

use RemoteDataBlocks\Editor\DataBinding\BlockBindings;

// Global variables provided by WordPress for block rendering:
// $attributes (array): The block attributes.
// $content (string): The block default content.
// $block (WP_Block): The block instance.

$block_state = BlockBindings::determine_block_state_to_render( $block );

if ( ! $block_state || ! isset( $attributes['mode'] ) || $block_state !== $attributes['mode'] ) {
	return null;
}

echo wp_kses_post( $content );
