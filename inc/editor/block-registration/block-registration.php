<?php

namespace RemoteDataBlocks\Editor;

defined( 'ABSPATH' ) || exit();

use RemoteDataBlocks\REST\RemoteData;

use function register_block_pattern;
use function register_block_type;

class BlockRegistration {
	public static function init() {
		add_action( 'init', [ __CLASS__, 'register_blocks' ], 50, 0 );
	}

	public static function register_blocks() {
		$remote_data_blocks_config = [];
		$scripts_to_localize       = [];

		foreach ( ConfigurationLoader::get_block_names() as $block_name ) {
			$block_path = REMOTE_DATA_BLOCKS__PLUGIN_DIRECTORY . '/build/blocks/context-container';
			$config     = ConfigurationLoader::get_configuration( $block_name );

			$overrides = array_filter( $config['queries']['__DISPLAY__']->input_variables, function ( $input_var ) {
				return isset( $input_var['overrides'] );
			} );

			// Set available bindings from the display query output mappings.
			$available_bindings = [];
			foreach ( $config['queries']['__DISPLAY__']->output_variables['mappings'] ?? [] as $key => $mapping ) {
				$available_bindings[ $key ] = [
					'name' => $mapping['name'],
					'type' => $mapping['type'],
				];
			}

			$remote_data_blocks_config[ $block_name ] = [
				'availableBindings' => $available_bindings,
				'loop'              => $config['loop'],
				'name'              => $block_name,
				'overrides'         => $overrides,
				'panels'            => $config['panels'],
				'settings'          => [
					'category' => 'remote-data-blocks',
					'title'    => $config['title'],
				],
			];

			$block_options = [
				'name'  => $block_name,
				'title' => $config['title'],
			];

			// Loop queries are dynamic blocks that render a list of items using the
			// inner blocks as a template.
			if ( $config['loop'] ) {
				$block_options['render_callback'] = [ BlockBindings::class, 'loop_block_render_callback' ];
			}

			$block_type = register_block_type( $block_path, $block_options );

			$scripts_to_localize[] = $block_type->editor_script_handles[0];

			// Register a default pattern that simply displays the available data.
			BlockPatterns::register_default_block_pattern( $block_name, $config['title'], $config['queries']['__DISPLAY__'] );

			// Register any user-provided patterns.
			foreach ( $config['patterns'] as $pattern_name => $pattern_options ) {
				register_block_pattern( $pattern_name, $pattern_options );
			}
		}

		foreach ( array_unique( $scripts_to_localize ) as $script_handle ) {
			wp_localize_script( $script_handle, 'REMOTE_DATA_BLOCKS', [
				'config'   => $remote_data_blocks_config,
				'rest_url' => RemoteData::get_url(),
			] );
		}
	}
}
