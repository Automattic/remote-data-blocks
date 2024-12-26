<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Editor\DataBinding;

use RemoteDataBlocks\Editor\BlockManagement\ConfigStore;

defined( 'ABSPATH' ) || exit();

class QueryOverrides {
	public static function init(): void {
		add_filter( 'query_vars', [ __CLASS__, 'add_query_vars' ] );
	}

	/**
	 * Register the query vars indicated as potential overrides in configured blocks.
	 */
	public static function add_query_vars( array $vars ): array {
		$query_vars = [];

		foreach ( ConfigStore::get_block_configurations() as $config ) {
			foreach ( $config['query_input_overrides'] as $override ) {
				switch ( $override['source_type'] ?? '' ) {
					case 'query_var':
						$query_vars[] = $override['source'];
						break;
				}
			}
		}

		return array_merge( $vars, $query_vars );
	}
}
