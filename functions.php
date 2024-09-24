<?php declare(strict_types = 1);

/**
 * Remote Data Blocks API
 *
 * This file contains functions, in the global namespace, for registering and
 * interacting with Remote Data Blocks.
 *
 * @package remote-data-blocks
 */

use RemoteDataBlocks\Config\QueryContext\QueryContextInterface;
use RemoteDataBlocks\Editor\BlockManagement\ConfigRegistry;

/**
 * Register a remote data block.
 *
 * @param string                $block_name The block name.
 * @param QueryContextInterface $get_query  The query used to fetch the remote data.
 */
function register_remote_data_block( string $block_name, QueryContextInterface $get_query ): void {
	ConfigRegistry::register_block( $block_name, $get_query );
}

/**
 * Register a remote data loop block, which displays a collection of remote data
 * items.
 *
 * @param string                $block_name           The block name.
 * @param QueryContextInterface $get_collection_query The query used to fetch the remote data collection.
 */
function register_remote_data_loop_block( string $block_name, QueryContextInterface $get_collection_query ): void {
	ConfigRegistry::register_loop_block( $block_name, $get_collection_query );
}

/**
 * Register a remote data list query to allow users to choose a remote data item
 * from a list.
 *
 * @param string                $block_name           The block name.
 * @param QueryContextInterface $get_collection_query The query used to fetch the remote data collection.
 */
function register_remote_data_list_query( string $block_name, QueryContextInterface $get_collection_query ): void {
	ConfigRegistry::register_list_query( $block_name, $get_collection_query );
}

/**
 * Register a remote data search query to allow users to search for a remote data
 * item.
 *
 * @param string                $block_name              The block name.
 * @param QueryContextInterface $search_collection_query The query used to search the remote data collection.
 */
function register_remote_data_search_query( string $block_name, QueryContextInterface $search_collection_query ): void {
	ConfigRegistry::register_search_query( $block_name, $search_collection_query );
}

/**
 * Register a block pattern that can used with a remote data block.
 *
 * @param string $block_name       The block name.
 * @param string $pattern_name     The pattern name.
 * @param string $pattern_html     The pattern HTML.
 * @param array  $pattern_options  The pattern options.
 */
function register_remote_data_block_pattern( string $block_name, string $pattern_name, string $pattern_html, array $pattern_options = [] ): void {
	ConfigRegistry::register_block_pattern( $block_name, $pattern_name, $pattern_html, $pattern_options );
}

/**
 * Register a remote data page.
 *
 * @param string $block_name The block name.
 * @param string $slug       The page slug.
 */
function register_remote_data_page( string $block_name, string $slug ): void {
	ConfigRegistry::register_page( $block_name, $slug );
}
