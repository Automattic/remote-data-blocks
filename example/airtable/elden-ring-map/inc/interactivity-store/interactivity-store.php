<?php

namespace RemoteDataBlocks\Example\Airtable\EldenRingMap;

defined( 'ABSPATH' ) || exit();

// use RemoteDataBlocks\Editor\BlockBindings;
use RemoteDataBlocks\Editor\ConfigurationLoader;
use RemoteDataBlocks\REST\RemoteData;
use WP_Block;

class InteractivityStore {
	public static function get_map_interactive_context( WP_Block $block ): array {
		// TODO: get from BlockBindings::get_value( [ 'field' => 'coordinates' ], $block );

		$coordinates = [
			[
				'x' => 0.5,
				'y' => 0.5,
			],
			[
				'x' => 0.25,
				'y' => 0.25,
			],
			[
				'x' => 0.75,
				'y' => 0.75,
			],
		]; 

		return [
			'coordinates' => $coordinates,
		];
	}

	public static function get_map_interactive_state(): array {
		$block_name = ConfigurationLoader::get_block_name( 'Elden Ring Map' );
		$rest_url   = RemoteData::get_url() . '?_envelope=true';

		return [
			'blockName' => $block_name,
			'restUrl'   => $rest_url,
		];
	}

	public static function get_store_name(): string {
		return 'remote-data-blocks/airtable-elden-ring-map';
	}
}
