<?php declare(strict_types = 1);

use RemoteDataBlocks\Example\Shopify\InteractivityStore;

$interactive_context = InteractivityStore::get_cart_button_interactive_context( $block );
$public_store_name   = InteractivityStore::get_store_name();

?>
<div
	data-wp-interactive="<?php echo esc_attr( $public_store_name ); ?>"
	<?php echo get_block_wrapper_attributes(); ?>
	<?php echo wp_interactivity_data_wp_context( $interactive_context ); ?>
>
	<?php echo wp_kses_post( $content ); ?>
	<?php if ( is_string( $interactive_context['variantId'] ) ) : ?>
		<div className="remote-data-blocks-cart-controls">
			<button
				data-wp-on--click="actions.addToCart"
				data-wp-bind--hidden="state.quantityInCart"
			>
				Add to Cart
			</button>
			<button
				data-wp-on--click="actions.removeFromCart"
				data-wp-bind--hidden="!state.quantityInCart"
			>
				Remove from Cart
			</button>
			<span
				className="remote-data-blocks-db-shopify-cart-count"
				data-wp-bind--hidden="!state.quantityInCart"
				data-wp-text="state.quantityInCart"
			></span>
		</div>
	<?php endif; ?>
</div>
