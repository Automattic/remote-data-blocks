<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\Capgemini\Jobs;

defined( 'ABSPATH' ) || exit();

use RemoteDataBlocks\Editor\DataBinding\BlockBindings;
use RemoteDataBlocks\Editor\BlockManagement\ConfigStore;
use RemoteDataBlocks\REST\RemoteDataController;
use WP_Block;

class InteractivityStore {
	public static function get_context( WP_Block $block ): array {
		$product_title = BlockBindings::get_value( [ 'field' => 'title' ], $block );
		$variant_id = BlockBindings::get_value( [ 'field' => 'variant_id' ], $block );

		return [
			'title' => $product_title,
			'variantId' => $variant_id,
		];
	}

	public static function get_initial_state(): array {
		$block_name = ConfigStore::get_block_name( 'Job Search Results' );
		$rest_url = RemoteDataController::get_url();

		return [
			'blockName' => $block_name,
			'restUrl' => $rest_url,
		];
	}

	public static function get_store_name(): string {
		return 'remote-data-blocks/capgemini-jobs';
	}
}
