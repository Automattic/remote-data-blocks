<?php

namespace RemoteDataBlocks\Example\Airtable\EldenRingMap;

defined( 'ABSPATH' ) || exit();

use RemoteDataBlocks\Editor\DataBinding\BlockBindings;
use WP_Block;

class InteractivityStore {
	public static function get_map_interactive_context( WP_Block $block ): array {
		$query_context = [
			'blockName'  => 'remote-data-blocks/elden-ring-location', // Why doesn't this match: $block->name,
			'queryInput' => $block->context['remote-data-blocks/remoteData']['queryInput'],
		];
		$response      = BlockBindings::execute_query( $query_context, 'GET' );
		$coordinates   = array_map( function ( $value ) {
			$result = $value['result'];
			return [
				'name' => $result['map_name']['value'],
				'x'    => $result['x']['value'],
				'y'    => $result['y']['value'],
			];
		}, $response['results'] );

		// $fallback_coordinates = $block->context['remote-data-blocks/remoteData']['results'];

		return [
			'coordinates' => $coordinates,
		];
	}

	public static function get_map_interactive_state(): array {
		// $block_name = ConfigurationLoader::get_block_name( 'Elden Ring Map' );
		// $rest_url   = RemoteData::get_url() . '?_envelope=true';

		return [
			'blockName' => 'Elden Ring Map',
			'restUrl'   => '',
		];
	}

	public static function get_store_name(): string {
		return 'remote-data-blocks/elden-ring-map';
	}
}
