<?php

use RemoteDataBlocks\Example\Shopify\InteractivityStore;

$public_store_name = InteractivityStore::get_store_name();

wp_interactivity_state( $public_store_name, InteractivityStore::get_cart_interactive_state() );

?>
<div
	class="remote-data-blocks-shopify-cart-container"
	data-wp-interactive="<?php echo esc_attr( $public_store_name ); ?>"
	<?php echo get_block_wrapper_attributes(); ?>
>
	<div
		class="remote-data-blocks-shopify-cart"
		data-wp-on--click="actions.toggleDetails"
	>
		<span
			class="remote-data-blocks-shopify-cart-count"
			data-wp-text="state.totalItems"
		></span>
		Cart
		<div
			class="remote-data-blocks-shopify-cart-items"
			data-wp-bind--hidden="!state.detailsOpen"
		>
			<h3>Your cart</h3>
			<p data-wp-bind--hidden="state.totalItems">Your cart is empty.</p>
			<template data-wp-each="state.cartArray" >
				<p>
					<span
						class="remote-data-blocks-shopify-cart-count"
						data-wp-text="context.item.quantity"
					></span>
					<span data-wp-text="context.item.title"></span>
				</p>
			</template>
			<div data-wp-bind--hidden="!state.checkoutUrl">
				<a
					data-wp-bind--href="state.checkoutUrl"
				>
					Checkout
				</a>
			</div>
		</div>
	</div>
</div>
