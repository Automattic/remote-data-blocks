<?php

namespace RemoteDataBlocks\Logging;

use Psr\Log\LoggerInterface;

defined( 'ABSPATH' ) || exit();

class LoggerManager {
	/**
	 * The logger instance.
	 *
	 * @var LoggerInterface|null
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
	 * @return LoggerInterface
	 */
	public static function instance(): LoggerInterface {
		if ( null === self::$instance ) {
			self::$instance = Logger::create( self::$log_namespace );
		}

		return self::$instance;
	}
}
