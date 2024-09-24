<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Editor\DataBinding;

use RemoteDataBlocks\Editor\BlockManagement\ConfigStore;

defined( 'ABSPATH' ) || exit();

class QueryOverrides {
	public static function init() {
		add_filter( 'query_vars', [ __CLASS__, 'add_query_vars' ] );
	}

	/**
	 * Register the query vars indicated as potential overrides in display queries.
	 */
	public static function add_query_vars( $vars ) {
		$query_vars = [];

		// Find all of the query variable overrides defined in display queries.
		foreach ( ConfigStore::get_block_names() as $block_name ) {
			$config = ConfigStore::get_configuration( $block_name );

			if ( ! isset( $config['queries']['__DISPLAY__']->input_variables ) ) {
				continue;
			}

			foreach ( $config['queries']['__DISPLAY__']->input_variables as $key => $input_var ) {
				if ( ! isset( $input_var['overrides'] ) ) {
					continue;
				}

				foreach ( $input_var['overrides'] as $override ) {
					switch ( $override['type'] ?? '' ) {
						case 'query_var':
							$query_vars[] = $override['target'];
							break;
						case 'url':
							$query_vars[] = $key;
							break;
					}
				}
			}
		}

		return array_merge( $vars, $query_vars );
	}
}
