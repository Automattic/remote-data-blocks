<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Config\BlockAttribute;

use RemoteDataBlocks\Config\ArraySerializable;
use RemoteDataBlocks\Validation\ConfigSchemas;
use WP_Error;

/**
 * RemoteDataBlockAttribute class
 *
 * Represents the "remoteData" block attribute for remote data blocks.
 */
class RemoteDataBlockAttribute extends ArraySerializable {
	/**
	 * @inheritDoc
	 */
	public static function get_config_schema(): array {
		return ConfigSchemas::get_remote_data_block_attribute_config_schema();
	}

	/**
	 * @inheritDoc
	 */
	public static function migrate_config( array $config ): array|WP_Error {
		// Provide some defaults to prevent constant defensive checks.
		$defaults = [
			'enabledOverrides' => [],
			'metadata' => [],
			'pagination' => [],
			'results' => [],
		];

		$config = array_merge( $defaults, $config );

		// Migrate the singular "queryInput" to the plural "queryInputs".
		if ( ! isset( $config['queryInputs'] ) ) {
			$config['queryInputs'] = [ $config['queryInput'] ?? [] ];
			unset( $config['queryInput'] );
		}

		return $config;
	}
}
