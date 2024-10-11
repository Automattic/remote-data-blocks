<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\Shopify;

defined( 'ABSPATH' ) || exit();

use RemoteDataBlocks\Editor\BlockBindings;
use RemoteDataBlocks\Editor\ConfigStore;
use RemoteDataBlocks\REST\RemoteData;
use WP_Block;

class InteractivityStore {
	public static function get_cart_button_interactive_context( WP_Block $block ): array {
		$product_title = BlockBindings::get_value( [ 'field' => 'title' ], $block );
		$variant_id    = BlockBindings::get_value( [ 'field' => 'variant_id' ], $block );

		return [
			'title'     => $product_title,
			'variantId' => $variant_id,
		];
	}

	public static function get_cart_interactive_state(): array {
		$block_name = ConfigStore::get_block_name( 'Shopify Product' );
		$rest_url   = RemoteData::get_url() . '?_envelope=true';

		return [
			'blockName' => $block_name,
			'restUrl'   => $rest_url,
		];
	}

	public static function get_store_name(): string {
		return 'remote-data-blocks/shopify';
	}
}
