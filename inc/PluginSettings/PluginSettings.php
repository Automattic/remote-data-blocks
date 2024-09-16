<?php

namespace RemoteDataBlocks\PluginSettings;

use RemoteDataBlocks\REST\DatasourceController;
use RemoteDataBlocks\REST\AuthController;
use function wp_get_environment_type;
use function wp_is_development_mode;

defined( 'ABSPATH' ) || exit();

class PluginSettings {
	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'add_options_page' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_settings_assets' ] );
		add_action( 'pre_update_option_remote_data_blocks_config', [ __CLASS__, 'pre_update_option_remote_data_blocks_config' ], 10, 3 );
		add_action( 'option_remote_data_blocks_config', [ __CLASS__, 'decrypt_option' ], 10, 3 );
		add_action( 'rest_api_init', [ __CLASS__, 'init_rest_routes' ] );
	}

	public static function add_options_page() {
		add_options_page(
			__( 'Remote Data Blocks Settings', 'remote-data-blocks' ),
			__( 'Remote Data Blocks', 'remote-data-blocks' ),
			'manage_options',
			'remote-data-blocks-settings',
			[ __CLASS__, 'settings_page_content' ]
		);
	}

	public static function settings_page_content() {
		printf(
			'<div id="remote-data-blocks-settings-wrapper">
				<div id="remote-data-blocks-settings">%s</div>
			</div>',
			esc_html__( 'Loading…', 'remote-data-blocks' )
		);
	}

	public static function init_rest_routes() {
		$controller = new DatasourceController();
		$controller->register_routes();

		$auth_controller = new AuthController();
		$auth_controller->register_routes();
	}

	public static function enqueue_settings_assets( $admin_page ) {
		if ( 'settings_page_remote-data-blocks-settings' !== $admin_page ) {
			return;
		}

		$asset_file = REMOTE_DATA_BLOCKS__PLUGIN_DIRECTORY . '/build/settings/index.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			wp_die( 'The settings asset file is missing. Run `npm run build` to generate it.' );
		}

		$asset = include $asset_file;

		wp_register_script(
			'remote-data-blocks-settings',
			plugins_url( 'build/settings/index.js', REMOTE_DATA_BLOCKS__PLUGIN_ROOT ),
			$asset['dependencies'],
			$asset['version'],
			[ 'in_footer' => true ]
		);

		wp_localize_script(
			'remote-data-blocks-settings',
			'REMOTE_DATA_BLOCKS_SETTINGS',
			[
				...( self::is_dev() ? self::get_build() : [] ),
				'version' => self::get_version(),
			]
		);

		wp_enqueue_script( 'remote-data-blocks-settings' );

		wp_enqueue_style(
			'remote-data-blocks-settings-style',
			plugins_url( 'build/settings/index.css', REMOTE_DATA_BLOCKS__PLUGIN_ROOT ),
			array_filter(
				$asset['dependencies'],
				function ( $style ) {
					return wp_style_is( $style, 'registered' );
				}
			),
			$asset['version'],
		);
	}

	/**
	 * Get the current build information from the Git repository.
	 * - `branch` - The current branch / HEAD.
	 * - `hash` - The current commit hash.
	 *
	 * This function is only called in development mode by default
	 * (e.g. when `is_dev` returns true).
	 */
	public static function get_build() {
		$git_path  = REMOTE_DATA_BLOCKS__PLUGIN_DIRECTORY . '/.git';
		$head_path = $git_path . '/HEAD';
		if ( ! file_exists( $head_path ) ) {
			return [];
		}

		// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
		$head_contents = file_get_contents( $head_path );
		if ( ! preg_match( '/^ref: (refs\/heads\/(.+))/', $head_contents, $matches ) ) {
			return [];
		}

		$ref      = $matches[1];
		$branch   = $matches[2];
		$ref_path = $git_path . '/' . $ref;

		if ( ! file_exists( $ref_path ) ) {
			return [];
		}

		// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
		$hash = file_get_contents( $ref_path );
		return compact( 'branch', 'hash' );
	}

	/**
	 * Get the plugin version from the main plugin file.
	 */
	public static function get_version() {
		return REMOTE_DATA_BLOCKS__PLUGIN_VERSION;
	}

	/**
	 * Check if the current environment is development.
	 */
	public static function is_dev() {
		return 'development' === wp_get_environment_type() && wp_is_development_mode( 'plugin' );
	}

	public static function pre_update_option_remote_data_blocks_config( $new_value, $old_value ) {
		$encryptor = new \RemoteDataBlocks\WpdbStorage\DataEncryption();

		try {
			return $encryptor->encrypt( wp_json_encode( $new_value ) );
		} catch ( \Exception $e ) {
			add_settings_error(
				'remote_data_blocks_settings',
				'encryption_error',
				__( 'Error encrypting remote-data-blocks settings.', 'remote-data-blocks' )
			);
			return $old_value;
		}
	}

	public static function decrypt_option( $value ) {
		$decryptor = new \RemoteDataBlocks\WpdbStorage\DataEncryption();

		try {
			$decrypted = $decryptor->decrypt( $value );
			return json_decode( $decrypted );
		} catch ( \Exception $e ) {
			add_settings_error(
				'remote_data_blocks_settings',
				'decryption_error',
				__( 'Error decrypting remote-data-blocks settings.', 'remote-data-blocks' )
			);
			return null;
		}
	}
}
