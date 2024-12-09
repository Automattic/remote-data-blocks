<?php declare(strict_types = 1);

/**
 * Remote Data Blocks API
 *
 * This file contains functions, in the global namespace, for registering and
 * interacting with Remote Data Blocks.
 */

use RemoteDataBlocks\Editor\BlockManagement\ConfigRegistry;

/**
 * Register a remote data block.
 *
 * @param array<string, mixed> $block_config The block configuration.
 */
function register_remote_data_block( array $block_config ): void {
	ConfigRegistry::register_block( $block_config );
}

/**
 * Register a remote data page.
 *
 * @param string $block_name The block name.
 * @param string $slug       The page slug.
 */
function register_remote_data_page( string $block_name, string $slug, array $options = [] ): void {
	ConfigRegistry::register_page( $block_name, $slug, $options );
}
