<?php

namespace RemoteDataBlocks\Logging;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Stringable;
use function apply_filters;
use function do_action;

defined( 'ABSPATH' ) || exit();

/**
 * A simple PSR-3 logger implementation. Eventually this can be provided as a
 * Composer package and used by other WordPress VIP plugins.
 */
class Logger extends AbstractLogger {
	/**
	 * We use the factory pattern to provide access to namespaced instances.
	 *
	 * @var array Logger[]
	 */
	private static $instances = [];

	/**
	 * The minimum observed log level.
	 *
	 * @var string
	 */
	private $log_level;

	/**
	 * Log levels in ascending priority order.
	 */
	private $log_levels = [
		LogLevel::DEBUG     => 1,
		LogLevel::INFO      => 2,
		LogLevel::NOTICE    => 3,
		LogLevel::WARNING   => 4,
		LogLevel::ERROR     => 5,
		LogLevel::CRITICAL  => 6,
		LogLevel::ALERT     => 7,
		LogLevel::EMERGENCY => 8,
	];

	/**
	 * Protected constructor, use create factory method.
	 *
	 * @param string $namespace Optional namespace for the logger.
	 */
	protected function __construct( private string $namespace ) {
		$default_log_level = defined( 'WP_DEBUG' ) && WP_DEBUG ? LogLevel::DEBUG : LogLevel::WARNING;

		/**
		 * Filter the log level threshhold. Must supply a valid PSR log level.
		 *
		 * @param string $default_log_level The default log level.
		 * @since 0.1.0
		 */
		$this->log_level = apply_filters( 'wpcomvip_log_level', $default_log_level );

		// If an invalid log level is provided, revert to the default.
		if ( ! isset( $this->log_levels[ $this->log_level ] ) ) {
			$this->log_level = $default_log_level;
			$this->log( LogLevel::ERROR, 'Invalid log level provided to "wpcomvip_log_level" filter.', [ 'level' => $this->log_level ] );
		}
	}

	/**
	 * Create reusable namespaced instances of the logger.
	 *
	 * @param string $namespace Optional namespace for the logger.
	 */
	public static function create( string $namespace = 'default' ): self {
		if ( ! isset( self::$instances[ $namespace ] ) ) {
			self::$instances[ $namespace ] = new self( $namespace );
		}

		return self::$instances[ $namespace ];
	}

	/**
	 * Returns true if log level 1 is higher than log level 2, otherwise false.
	 *
	 * @param mixed $level1 The first log level.
	 * @param mixed $level2 The second log level.
	 * @return bool
	 */
	public function is_log_level_higher( mixed $level1, mixed $level2 ): bool {
		if ( ! isset( $this->log_levels[ $level1 ], $this->log_levels[ $level2 ] ) ) {
			return false;
		}

		return $this->log_levels[ $level1 ] >= $this->log_levels[ $level2 ];
	}

	/**
	 * PSR log implementation.
	 */
	public function log( mixed $level, Stringable|string $message, array $context = [] ): void {
		if ( ! $this->should_log( $level ) ) {
			return;
		}

		/**
		 * Action hook for logging messages. Hook into this to perform your own
		 * logging.
		 *
		 * @param string $namespace The logger namespace.
		 * @param string $level     The log level.
		 * @param string $message   The log message.
		 * @param array  $context   Additional context for the log message.
		 * @since 0.1.0
		 */
		do_action( 'wpcomvip_log', $this->namespace, strval( $level ), strval( $message ), $context );

		$this->log_to_query_monitor( $level, $message, $context );
	}

	private function log_to_query_monitor( mixed $level, string $message, array $context = [] ): void {
		/**
		 * Filter to determine if a message should be logged to Query Monitor.
		 *
		 * @param bool   $should_log_to_query_monitor Whether the message should be logged to Query Monitor.
		 * @param string $level                       The log level.
		 */
		$should_log_to_query_monitor = apply_filters( 'wpcomvip_log_to_query_monitor', true, $level );

		if ( ! $should_log_to_query_monitor ) {
			return;
		}

		$action = sprintf( 'qm/%s', $level );
		$qm_log = trim( sprintf( '%s %s', $message, empty( $context ) ? '' : wp_json_encode( $context ) ) );

		// https://querymonitor.com/wordpress-debugging/profiling-and-logging/#logging
		do_action( $action, $qm_log );
	}

	/**
	 * Determine if a message should be logged based on the log level.
	 */
	private function should_log( mixed $level ): bool {
		if ( ! isset( $this->log_levels[ $level ] ) || ! is_string( $level ) ) {
			$this->log( LogLevel::ERROR, 'Invalid log level provided to logger.', [ 'level' => $level ] );
			return false;
		}

		return $this->is_log_level_higher( $level, $this->log_level );
	}
}
