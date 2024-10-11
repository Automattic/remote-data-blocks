<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\Airtable\EldenRingMap;

defined( 'ABSPATH' ) || exit();

use RemoteDataBlocks\Editor\DataBinding\BlockBindings;
use WP_Block;

class InteractivityStore {
	public static function get_map_interactive_context( WP_Block $block ): array {
		$query_context = [
			'blockName'  => $block->context[ BlockBindings::$context_name ]['blockName'],
			'queryInput' => $block->context[ BlockBindings::$context_name ]['queryInput'],
		];
		$response      = BlockBindings::execute_query( $query_context, 'GET Map Coordinates' );
		$coordinates   = array_map( function ( $value ) {
			$result = $value['result'];
			return [
				'name' => $result['map_name']['value'],
				'x'    => $result['x']['value'],
				'y'    => $result['y']['value'],
			];
		}, $response['results'] );

		/**
		 * We have the coordinate data that was saved in the Editor in:
		 * $block->context[ BlockBindings::$context_name ]['results']
		 * 
		 * We can potentially use it as a fallback if the query fails.
		 */

		return [
			'coordinates' => $coordinates,
		];
	}
}
