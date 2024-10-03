<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Editor\BlockManagement;

defined( 'ABSPATH' ) || exit();

use RemoteDataBlocks\Logging\LoggerManager;
use Psr\Log\LoggerInterface;
use RemoteDataBlocks\Data\DataSourceProviderInterface;

use function sanitize_title;

class ConfigStore implements DataSourceProviderInterface {
	/**
	 * @var array<string, array<string, mixed>>
	 */
	private static array $configurations;
	private static LoggerInterface $logger;

	public static function init( ?LoggerInterface $logger = null ): void {
		self::$configurations = [];
		self::$logger         = $logger ?? LoggerManager::instance();
	}

	/**
	 * Convert a block title to a block name. Mainly this is to reduce the burden
	 * of configuration and to ensure that block names are unique (since block
	 * titles must be unique).
	 *
	 */
	public static function get_block_name( string $block_title ): string {
		return 'remote-data-blocks/' . sanitize_title( $block_title );
	}

	/**
	 * Get all registered block names.
	 *
	 * @return string[]
	 */
	public static function get_block_names(): array {
		return array_keys( self::$configurations );
	}

	/**
	 * Get the configuration for a block.
	 */
	public static function get_configuration( string $block_name ): ?array {
		if ( ! self::is_registered_block( $block_name ) ) {
			self::$logger->error( sprintf( 'Block %s has not been registered', $block_name ) );
			return null;
		}

		return self::$configurations[ $block_name ];
	}

	/**
	 * Set or update the configuration for a block.
	 */
	public static function set_configuration( string $block_name, array $config ): void {
		// @TODO: Validate config shape.
		self::$configurations[ $block_name ] = $config;
	}

	/**
	 * Check if a block is registered.
	 */
	public static function is_registered_block( string $block_name ): bool {
		return isset( self::$configurations[ $block_name ] );
	}

	public static function get_data_sources(): array {
		/* do the thing */
	}

	public static function get( string $whatever_uid ): ?array {
		/* do the thing */
	}

	public static function find_by( string $key, mixed $value ): ?array {
		/* do the thing */
	}
}
