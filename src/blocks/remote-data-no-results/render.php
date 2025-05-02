<?php declare(strict_types = 1);

use RemoteDataBlocks\Editor\DataBinding\BlockBindings;

// Global variables provided by WordPress for block rendering:
// $attributes (array): The block attributes.
// $content (string): The block default content.
// $block (WP_Block): The block instance.

$state = BlockBindings::get_empty_or_error_state_for_block( $block );

// If the state is not set to empty or error, and that doesn't match the block's attributes, then we don't need to render the block.
if ( ! $state || ! isset( $attributes['mode'] ) || $state !== $attributes['mode'] ) {
	return null;
}

echo wp_kses_post( $content );
