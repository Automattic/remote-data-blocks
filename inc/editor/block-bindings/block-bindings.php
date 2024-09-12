<?php

namespace RemoteDataBlocks\Editor;

defined( 'ABSPATH' ) || exit();

use RemoteDataBlocks\Editor\ConfigurationLoader;
use RemoteDataBlocks\Logging\LoggerManager;
use WP_Block;

use function register_block_bindings_source;

class BlockBindings {
	public static string $context_name   = 'remote-data-blocks/remoteData';
	public static string $binding_source = 'remote-data/binding';

	public static function init(): void {
		add_action( 'init', [ __CLASS__, 'register_block_bindings' ], 50, 0 );
		add_filter( 'register_block_type_args', [ __CLASS__, 'inject_context_for_synced_patterns' ], 10, 2 );
	}

	/**
	 * Register the block bindings source for our plugin.
	 */
	public static function register_block_bindings(): void {
		register_block_bindings_source( self::$binding_source, [
			'label'              => __( 'Remote Field Binding', 'remote-data-blocks' ),
			'get_value_callback' => [ __CLASS__, 'get_value' ],
			'uses_context'       => [ self::$context_name ],
		] );
	}

	/**
	 * WORKAROUND FOR WP CORE ISSUE: CONTEXT INHERITANCE FOR SYNCED PATTERNS
	 * ===
	 *
	 * Synced patterns are implemented as a special block type (`core/block`) with
	 * a `ref` attribute that points to the post ID of the synced pattern. It is a
	 * dynamic block, so it has a render callback function that is responsible for
	 * loading the pattern and rendering it.
	 *
	 * https://github.com/WordPress/wordpress/blob/6.6.1/wp-includes/class-wp-block.php#L519
	 * https://github.com/WordPress/wordpress/blob/6.6.1/wp-includes/blocks/block.php#L109
	 * https://github.com/WordPress/wordpress/blob/6.6.1/wp-includes/blocks/block.php#L19
	 * https://github.com/WordPress/wordpress/blob/6.6.1/wp-includes/blocks/block.php#L90
	 *
	 * Unfortunately, the render callback function delegates to `do_blocks()`,
	 * which does not allow passing context and therefore breaks the context
	 * inheritance chain for its inner blocks. Many block bindings rely on this
	 * context inheritance to work, including ours. :/
	 *
	 * Core faces this exact same issue for sync pattern overrides, which are
	 * implemented as a block binding. Core added a narrowly targeted workaround
	 * for their binding, which adds a temporary filter to supply context
	 * to the inner blocks of synced patterns. However, their workaround is
	 * hardcoded for synced patterns, so we cannot benefit from it:
	 *
	 * https://github.com/WordPress/wordpress/blob/6.6.1/wp-includes/blocks/block.php#L83-L87
	 *
	 * However, we can add our own similar workaround. It requires filtering the
	 * block type args for the `core/block` block type to make two changes:
	 *
	 * 1. Add our context to the `uses_context` array so the the synced pattern
	 *    block has access to it. We do this only to make the context available to
	 *    our changes in step 2.
	 *
	 * 2. Wrap the block render callback function with a new function. This function
	 *    adds a temporary filter to inject the context for inner blocks.
	 */
	public static function inject_context_for_synced_patterns( array $block_type_args, string $block_name ): array {
		if ( 'core/block' !== $block_name ) {
			return $block_type_args;
		}

		// Add our context to the `uses_context` array so the the synced pattern block
		// has access to it.
		$block_type_args['uses_context'] = array_merge(
			$block_type_args['uses_context'] ?? [],
			[ self::$context_name ]
		);

		// Wrap the existing block render callback.
		$block_type_args['render_callback'] = static function ( array $attributes, string $content, WP_Block $synced_pattern_block ) use ( $block_type_args ): string {

			// Add a temporary filter to inject the context for inner blocks.
			$filter_block_context = static function ( array $context ) use ( $synced_pattern_block ): array {
				if ( isset( $synced_pattern_block->context ) ) {
					return array_merge( $context, $synced_pattern_block->context );
				}

				return $context;
			};
			add_filter( 'render_block_context', $filter_block_context, 10, 1 );

			// Call the original render callback.
			$rendered_content = call_user_func( $block_type_args['render_callback'], $attributes, $content, $synced_pattern_block );

			// Remove the temporary filter.
			remove_filter( 'render_block_context', $filter_block_context, 10, 1 );

			return $rendered_content;
		};

		return $block_type_args;
	}

	/**
	 * Load possible query input overrides for a block binding. Allowed overrides
	 * are defined in the query configuration. The block editor determines if an
	 * override is applied.
	 */
	private static function apply_query_input_overrides( array $query_input, array $overrides, string $block_name ): array {
		$query_input_overrides = [];

		foreach ( $overrides as $key => $override ) {
			// Override was provided, but query input does not have the key.
			if ( ! isset( $query_input[ $key ] ) ) {
				continue;
			}

			$override_value = '';

			switch ( $override['type'] ) {
				// Source the input variable override from a query variable.
				case 'query_var':
					$override_value = get_query_var( $override['target'], '' );
					break;
				case 'url':
					$override_value = get_query_var( $key, '' );
					break;
			}

			if ( ! empty( $override_value ) ) {
				$query_input_overrides[ $key ] = $override_value;
			}
		}

		/**
		 * Filter the query input overrides for a block binding.
		 *
		 * @param array  $query_input_overrides The query input overrides.
		 * @param array  $query_input           The original query input.
		 * @param string $block_name            The block name.
		 */
		$overrides = apply_filters(
			'remote_data_blocks_query_input_overrides',
			$query_input_overrides,
			$query_input,
			$block_name
		);

		return array_merge( $query_input, $query_input_overrides );
	}

	public static function execute_query( array $block_context, string $operation_name ): array|null {
		$block_name   = $block_context['blockName'];
		$query_input  = $block_context['queryInput'];
		$overrides    = $block_context['queryInputOverrides'] ?? [];
		$block_config = ConfigurationLoader::get_configuration( $block_name );

		if ( null === $block_config ) {
			return null;
		}

		try {
			$query_config = $block_config['queries']['__DISPLAY__'];
			$query_input  = self::apply_query_input_overrides( $query_input, $overrides, $block_name );

			$query_runner  = $query_config->get_query_runner();
			$query_results = $query_runner->execute( $query_input );

			if ( is_wp_error( $query_results ) ) {
				self::log_error( 'Error executing query for block binding: ' . $query_results->get_error_message(), $block_name, $operation_name );
				return null;
			}

			return $query_results;
		} catch ( \Exception $e ) {
			self::log_error( 'Unexpected exception for block binding: ' . $e->getMessage(), $block_name, $operation_name );
			return null;
		}
	}

	public static function get_value( array $source_args, WP_Block|array $block ): string {
		// We may be passed a block instance (by core block bindings) or a block
		// array (by our hooks into the Block Data API).
		if ( $block instanceof WP_Block ) {
			$fallback_content = $block->attributes['content'] ?? '';
			$block_context    = $block->context[ self::$context_name ] ?? [];
		} else {
			$fallback_content = $block['attributes']['content'] ?? '';
			$block_context    = $block['context'][ self::$context_name ] ?? [];
		}

		// Fallback to the content if we don't have the expected context.
		if ( ! isset( $block_context['blockName'] ) || ! isset( $block_context['queryInput'] ) ) {
			self::log_error( sprintf( 'Missing block context for block binding %s', self::$context_name ), 'unknown' );
			return $fallback_content;
		}


		$value = self::get_remote_value( $block_context, $source_args );

		if ( ! is_string( $value ) ) {
			self::log_error( 'Received non-string value for block binding', $block_context['blockName'], $source_args['field'] );
			return $fallback_content;
		}

		return $value;
	}

	public static function get_remote_value( array $block_context, array $source_args ): string|null {
		$block_name = $block_context['blockName'];
		$field_name = $source_args['field'];
		$index      = $source_args['index'] ?? 0; // Index is only set for loop queries.

		if ( isset( $source_args['name'] ) && $source_args['name'] !== $block_name ) {
			self::log_error( 'Block binding belongs to a different remote data block', $block_name, $field_name );
			return null;
		}

		$query_results = self::execute_query( $block_context, $field_name );

		if ( ! isset( $query_results['results'][ $index ]['result'][ $field_name ]['value'] ) ) {
			self::log_error( 'Cannot resolve field for block binding', $block_name, $field_name );
			return null;
		}

		// Prepend label to value if provided. Class name should match the one
		// generated by the block editor script.
		$value = $query_results['results'][ $index ]['result'][ $field_name ]['value'];
		if ( ! empty( $source_args['label'] ?? '' ) ) {
			return sprintf( '<span class="rdb-block-label">%s</span> %s', $source_args['label'], $value );
		}

		return $value;
	}

	public static function loop_block_render_callback( array $attributes, string $content, WP_Block $block ): string {
		// This is the parent block that provides the context, so we don't have
		// context available on the block's context property. However, context for
		// children blocks comes from this block's `remoteData` attribtue (see
		// block.json#providesContext), so we can access it directly.
		$block_context = $attributes['remoteData'];

		$loop_template         = $block->parsed_block['innerBlocks'];
		$loop_template_content = $block->parsed_block['innerContent'];
		$query_results         = self::execute_query( $block_context, 'loop' );

		if ( ! isset( $query_results['results'] ) ) {
			self::log_error( 'Cannot load results for data loop', $block->name, 'loop' );
			return $content;
		}

		$block->parsed_block['innerBlocks']  = [];
		$block->parsed_block['innerContent'] = [];

		// Loop through the query results and make a copy of the template for each
		// result, updating the bindings with the result index. This will be used
		// by the binding source to render the correct result.
		foreach ( array_keys( $query_results['results'] ) as $index ) {

			// Loop over the inner blocks of the template and update the bindings to
			// include the current index.
			$updated_inner_blocks               = self::add_loop_index_to_inner_blocks( $loop_template, $index );
			$block->parsed_block['innerBlocks'] = array_merge( $block->parsed_block['innerBlocks'], $updated_inner_blocks );

			// We don't care too much what the content is, we just need to make sure
			// it's there so that it can be looped over by WP_Block#render.
			$block->parsed_block['innerContent'] = array_merge( $block->parsed_block['innerContent'], $loop_template_content );
		}

		// Create an updated block with the new inner blocks and content.
		$updated_block = new WP_Block( $block->parsed_block );

		// Render the updated block but set dynamic to false so that we don't have
		// recursion.
		return $updated_block->render( [ 'dynamic' => false ] );
	}

	/**
	 * Recursively add the loop index to the bindings of the inner blocks.
	 *
	 * @param array $inner_blocks The inner blocks to update.
	 * @param int   $index        The loop index.
	 * @return array The updated inner blocks.
	 */
	private static function add_loop_index_to_inner_blocks( array $inner_blocks, int $index ): array {
		foreach ( $inner_blocks as &$inner_block ) {
			// Update bindings with the result index.
			foreach ( $inner_block['attrs']['metadata']['bindings'] ?? [] as $target => $binding ) {
				if ( ! isset( $binding['source'] ) || $binding['source'] !== self::$binding_source ) {
					continue; // Not our binding.
				}

				// Add the loop index to the binding args so that it can be read by
				// our binding source.
				$inner_block['attrs']['metadata']['bindings'][ $target ]['args']['index'] = $index;
			}

			// If this block has inner blocks, recurse.
			if ( isset( $inner_block['innerBlocks'] ) ) {
				$inner_block['innerBlocks'] = self::add_loop_index_to_inner_blocks( $inner_block['innerBlocks'], $index );
			}
		}

		return $inner_blocks;
	}

	public static function log_error( string $message, string $block_name, string $operation_name = 'unknown' ): void {
		$logger = LoggerManager::instance();
		$logger->error( sprintf( '%s %s (block: %s; operation: %s)', $message, self::$context_name, $block_name, $operation_name ) );
	}
}
