<?php

function register_leaflet_map_remote_data_block(): void {
	// Register the Leaflet script and stylesheet. The handles are referenced in
	// `block.json` for use in the block editor and the WordPress frontend.
	wp_register_style( 'leaflet-style', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4' );
	wp_register_script( 'leaflet-script', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true );

	/**
	 * Registers the block(s) metadata from the `blocks-manifest.php` and registers the block type(s)
	 * based on the registered block metadata.
	 * Added in WordPress 6.8 to simplify the block metadata registration process added in WordPress 6.7.
	 *
	 * @see https://make.wordpress.org/core/2025/03/13/more-efficient-block-type-registration-in-6-8/
	 */
	wp_register_block_types_from_metadata_collection( __DIR__ . '/build', __DIR__ . '/build/blocks-manifest.php' );
}
add_action( 'init', 'register_leaflet_map_remote_data_block' );
