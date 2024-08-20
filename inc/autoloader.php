<?php

namespace RemoteDataBlocks;

defined( 'ABSPATH' ) || exit();

class AutoLoader {
	public static function init() {
		spl_autoload_register( [ __CLASS__, 'autoload' ] );
	}

	public static function autoload( $class_name ) {
		$base_dir  = REMOTE_DATA_BLOCKS__PLUGIN_DIRECTORY . '/inc';
		$namespace = 'RemoteDataBlocks\\';

		// Check if the class is in our namespace.
		$len = strlen( $namespace );
		if ( strncmp( $namespace, $class_name, $len ) !== 0 ) {
			// If not, move to the next registered autoloader
			return;
		}

		// Get the relative class name.
		$relative_class_ref = substr( $class_name, $len );

		// Convert namespace separators to directory separators.
		$relative_path = str_replace( '\\', DIRECTORY_SEPARATOR, $relative_class_ref );

		// Convert PascalCase to kebab-case and lowercase.
		$relative_path = strtolower( preg_replace( '/([a-z0-9])([A-Z])/', '$1-$2', $relative_path ) );

		$base_name = basename( $relative_path );

		// Adjust relative path if looking for an interface so that it can be
		// colocated with its class.
		$interface_suffix = '-interface';
		$len              = strlen( $interface_suffix );
		if ( substr( $base_name, -$len ) === $interface_suffix ) {
			$relative_path = substr( $relative_path, 0, -$len );
		}

		// Full path to the file
		$file = sprintf( '%s/%s/%s.php', $base_dir, $relative_path, $base_name );

		// Include the file if it exists
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}

AutoLoader::init();
