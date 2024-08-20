<?php

namespace RemoteDataBlocks\Editor;

defined( 'ABSPATH' ) || exit();

class PatternEditor {
	public static function init() {
		register_post_meta( 'wp_block', '_remote_data_blocks_block_type', [
			'show_in_rest' => true,
			'single'       => true,
			'type'         => 'string',
		] );

		add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\PatternEditor::enqueue_block_editor_assets' );
	}

	public static function enqueue_block_editor_assets() {
		$asset_file = REMOTE_DATA_BLOCKS__PLUGIN_DIRECTORY . '/build/pattern-editor/index.asset.php';
	
		if ( ! file_exists( $asset_file ) ) {
			wp_die( 'The settings asset file is missing. Run `npm run build` to generate it.' );
		}
	
		$asset = include $asset_file;
	
		wp_enqueue_script(
			'remote-data-blocks-pattern-editor',
			plugins_url( 'build/pattern-editor/index.js', REMOTE_DATA_BLOCKS__PLUGIN_ROOT ),
			$asset['dependencies'],
			$asset['version'],
			[
				'in_footer' => true,
			]
		);
	}
}
