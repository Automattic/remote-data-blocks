<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Logging;

use function add_filter;
use function do_action;

defined( 'ABSPATH' ) || exit();

/**
 * A simple PSR-3 logger implementation. Eventually this can be provided as a
 * Composer package and used by other WordPress VIP plugins.
 */
class Logger extends AbstractLogger {
	/**
	 * Whether the factory has have been initialized. Done automatically on first use.
	 */
	private static bool $initialized = false;

	/**
	 * We use the factory pattern to provide access to namespaced instances.
	 *
	 * @var Logger[]
	 */
	private static array $instances = [];

	/**
	 * The minimum observed log level.
	 */
	private string $log_level;

	/**
	 * Protected constructor, use create factory method.
	 *
	 * @param string $namespace Optional namespace for the logger.
	 */
	protected function __construct( private string $namespace ) {
		$this->log_level = defined( 'WP_DEBUG' ) && WP_DEBUG ? LogLevel::DEBUG : LogLevel::WARNING;
	}

	/**
	 * Create reusable namespaced instances of the logger.
	 *
	 * @param string $namespace Optional namespace for the logger.
	 */
	public static function create( string $namespace = 'default' ): self {
		self::init();

		if ( ! isset( self::$instances[ $namespace ] ) ) {
			self::$instances[ $namespace ] = new self( $namespace );
		}

		return self::$instances[ $namespace ];
	}

	public static function init(): void {
		if ( self::$initialized ) {
			return;
		}

		self::$initialized = true;

		// Filter logger classes out of the stack traces. Using a static closure
		// keeps the filter from being added to the stack.
		add_filter( 'qm/trace/ignore_class', static function ( array $classes ): array {
			return array_merge( $classes, [
				AbstractLogger::class => true,
				Logger::class => true,
				LoggerManager::class => true,
			] );
		}, 10, 1 );
	}

	/**
	 * PSR log implementation.
	 */
	public function log( string $level, string $message, array $context = [] ): void {
		$level = strval( $level );
		$message = strval( $message );

		if ( ! LogLevel::is_log_level_higher( $level, $this->log_level ) ) {
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
		 */
		do_action( 'wpcomvip_log', $this->namespace, $level, $message, $context );

		// Prefix with a "source" name to help identify the source of the log message.
		if ( isset( $context['source'] ) ) {
			$message = sprintf( '[%s] %s', $context['source'], $message );
			unset( $context['source'] );
		}

		$this->log_to_query_monitor( $level, $message, $context );
	}

	private function log_to_query_monitor( string $level, string $message, array $context = [] ): void {
		$action = sprintf( 'qm/%s', $level );
		$qm_log = trim( sprintf( '%s %s', $message, empty( $context ) ? '' : wp_json_encode( $context ) ) );

		// https://querymonitor.com/wordpress-debugging/profiling-and-logging/#logging
		do_action( $action, $qm_log );
	}
}
