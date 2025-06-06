<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Config\Block;

use RemoteDataBlocks\Config\ArraySerializable;
use RemoteDataBlocks\Editor\BlockManagement\ConfigRegistry;
use RemoteDataBlocks\Validation\ConfigSchemas;
use WP_Error;

/**
 * RemoteDataBlock class
 *
 * Represents the block configuration for a remote data block.
 */
class RemoteDataBlock extends ArraySerializable {
	protected const DEPRECATED_RENDER_QUERY_KEY = 'render_query';
	protected const DEPRECATED_SELECTION_QUERIES_KEY = 'selection_queries';

	/**
	 * @inheritDoc
	 */
	public static function get_config_schema(): array {
		return ConfigSchemas::get_remote_data_block_config_schema();
	}

	/**
	 * @inheritDoc
	 */
	public static function migrate_config( array $config = [] ): array|WP_Error {
		// Nothing to migrate, return the block config as is.
		if ( isset( $config[ ConfigRegistry::PLACEHOLDERS_KEY ] ) ) {
			return $config;
		}

		$queries = [];
		$placeholders = [];

		if ( isset( $config[ self::DEPRECATED_RENDER_QUERY_KEY ]['query'] ) ) {
			$queries[ ConfigRegistry::DEPRECATED_DISPLAY_QUERY_KEY ] = $config[ self::DEPRECATED_RENDER_QUERY_KEY ]['query'];
			$placeholders[] = [
				'name' => 'Display',
				'query_key' => ConfigRegistry::DEPRECATED_DISPLAY_QUERY_KEY,
			];
			unset( $config[ self::DEPRECATED_RENDER_QUERY_KEY ] );
		}

		if ( isset( $config[ self::DEPRECATED_SELECTION_QUERIES_KEY ] ) ) {
			// Get the selection queries, inflate them, and add them to the queries array using the type as the key.
			foreach ( $config[ self::DEPRECATED_SELECTION_QUERIES_KEY ] as $selection_query ) {
				$queries[ $selection_query['type'] ] = $selection_query['query'];
			}

			unset( $config[ self::DEPRECATED_SELECTION_QUERIES_KEY ] );
		}

		// Set queries.
		$config[ ConfigRegistry::QUERIES_KEY ] = $queries;
		$config[ ConfigRegistry::PLACEHOLDERS_KEY ] = $placeholders;

		return $config;
	}
}
