<?php declare(strict_types = 1);

use RemoteDataBlocks\Editor\DataBinding\BlockBindings;

// Global variables provided by WordPress for block rendering:
// $attributes (array): The block attributes.
// $content (string): The block default content.
// $block (WP_Block): The block instance.

$state = BlockBindings::is_error_or_empty_state( $block );

// The state will only be true when the query gives back an error, or no results and the block attribute matched the response.
if ( ! $state ) {
	return null;
}

echo wp_kses_post( $content );
