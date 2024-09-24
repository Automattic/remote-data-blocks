<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Logging;

use Psr\Log\LoggerInterface;

defined( 'ABSPATH' ) || exit();

class LoggerManager {
	/**
	 * The logger instance.
	 *
	 */
	private static ?LoggerInterface $instance = null;

	/**
	 * The namespace for the logger.
	 *
	 */
	public static string $log_namespace = 'remote-data-blocks';

	/**
	 * Get the logger singleton instance.
	 *
	 */
	public static function instance(): LoggerInterface {
		if ( null === self::$instance ) {
			self::$instance = Logger::create( self::$log_namespace );
		}

		return self::$instance;
	}
}
