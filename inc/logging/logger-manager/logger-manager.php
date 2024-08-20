<?php

namespace RemoteDataBlocks\Logging;

defined( 'ABSPATH' ) || exit();

class LoggerManager {
	/**
	 * The logger instance.
	 *
	 * @var Logger|null
	 */
	private static $instance = null;

	/**
	 * The namespace for the logger.
	 *
	 * @var string
	 */
	public static $log_namespace = 'remote-data-blocks';

	/**
	 * Get the logger singleton instance.
	 *
	 * @return Logger
	 */
	public static function instance(): Logger {
		if ( null === self::$instance ) {
			self::$instance = Logger::create( self::$log_namespace );
		}

		return self::$instance;
	}
}
