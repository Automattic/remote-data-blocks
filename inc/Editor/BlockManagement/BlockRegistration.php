<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Editor\BlockManagement;

defined( 'ABSPATH' ) || exit();

use RemoteDataBlocks\Editor\BlockPatterns\BlockPatterns;
use RemoteDataBlocks\Editor\DataBinding\BlockBindings;
use RemoteDataBlocks\REST\RemoteDataController;
use function register_block_type;

class BlockRegistration {
	/**
	 * @var array<string, string>
	 */
	public static array $block_category = [
		'icon' => null,
		'slug' => 'remote-data-blocks',
		'title' => 'Remote Data Blocks',
	];

	public static function init(): void {
		add_action( 'init', [ __CLASS__, 'register_blocks' ], 50, 0 );
		add_filter( 'block_categories_all', [ __CLASS__, 'add_block_category' ], 10, 1 );
	}

	public static function add_block_category( array $block_categories ): array {
		array_push( $block_categories, self::$block_category );

		return $block_categories;
	}

	public static function register_blocks(): void {
		$remote_data_blocks_config = [];
		$scripts_to_localize = [];

		foreach ( ConfigStore::get_block_names() as $block_name ) {
			$block_path = REMOTE_DATA_BLOCKS__PLUGIN_DIRECTORY . '/build/blocks/remote-data-container';
			$config = ConfigStore::get_configuration( $block_name );

			$input_vars_with_overrides = array_filter( $config['queries']['__DISPLAY__']->input_schema, function ( $input_var ) {
				return isset( $input_var['overrides'] );
			} );

			$formatted_overrides = [];
			foreach ( $input_vars_with_overrides as $name => $input_var ) {
				$formatted_overrides[ $name ] = array_merge( $input_var, [
					'overrides' => array_map( function ( $override ) use ( $name ) {
						$display = '';
						switch ( $override['type'] ) {
							case 'query_var':
								$display = sprintf( '?%s={%s}', $override['target'], $name );
								break;
							case 'url':
								$display = sprintf( '/%s/{%s}', $override['target'], $name );
								break;
						}

						$override['display'] = $override['display'] ?? $display;

						return $override;
					}, $input_var['overrides'] ),
				] );
			}

			// Set available bindings from the display query output mappings.
			$available_bindings = [];
			foreach ( $config['queries']['__DISPLAY__']->output_schema['mappings'] ?? [] as $key => $mapping ) {
				$available_bindings[ $key ] = [
					'name' => $mapping['name'],
					'type' => $mapping['type'],
				];
			}

			// Create the localized data that will be used by our block editor script.
			$remote_data_blocks_config[ $block_name ] = [
				'availableBindings' => $available_bindings,
				'loop' => $config['loop'],
				'name' => $block_name,
				'overrides' => $formatted_overrides,
				'patterns' => $config['patterns'],
				'selectors' => $config['selectors'],
				'outputSchema' => $config['outputSchema'] ?? null,
				'settings' => [
					'category' => self::$block_category['slug'],
					'title' => $config['title'],
				],
			];

			$block_options = [
				'name' => $block_name,
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
			$default_pattern_name = BlockPatterns::register_default_block_pattern( $block_name, $config['title'], $config['queries']['__DISPLAY__'] );
			$remote_data_blocks_config[ $block_name ]['patterns']['default'] = $default_pattern_name;
		}

		foreach ( array_unique( $scripts_to_localize ) as $script_handle ) {
			wp_localize_script( $script_handle, 'REMOTE_DATA_BLOCKS', [
				'config' => $remote_data_blocks_config,
				'rest_url' => RemoteDataController::get_url(),
			] );
		}
	}
}
