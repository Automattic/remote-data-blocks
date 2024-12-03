<?php declare(strict_types = 1);

use RemoteDataBlocks\Example\Capgemini\Jobs\InteractivityStore;

$public_store_name = InteractivityStore::get_store_name();
$initial_state = InteractivityStore::get_initial_state();

wp_interactivity_state( $public_store_name, $initial_state );

?>
<div
	data-wp-interactive="<?php echo esc_attr( $public_store_name ); ?>"
	<?php echo get_block_wrapper_attributes(); ?>
>
	<h1>Filters</h1>
	<ul>
		<template data-wp-each--filter="state.filters">
			<li>
				<span data-wp-text="context.filter.type.value"></span>
				<ul>
					<template data-wp-each--item="context.items">
						<li class="filter-item">
							<input type="checkbox" data-wp-bind--name="context.filter.type.value" data-wp-on--change="actions.toggleFilter" />
							<span data-wp-text="context.item.value"></span>
							(<span data-wp-text="context.item.count"></span>)
						</li>
					</template>
				</ul>
			</li>
		</template>
		<?php foreach ( $initial_state['filters'] ?? [] as $filter ) : ?>
			<li data-wp-each-child>
				<span><?php echo esc_html( $filter['type']['value'] ); ?></span>
				<ul>
					<?php foreach ( $filter['items']['value'] ?? [] as $item ) : ?>	
					<li data-wp-each-child class="filter-item">
						<input type="checkbox" name="<?php echo esc_attr( $filter['type']['value'] ); ?>" />
						<span><?php echo esc_html( isset( $item['value'] ) ? $item['value'] : '' ); ?></span>
						(<span><?php echo esc_html( isset( $item['count'] ) ? $item['count'] : '' ); ?></span>)
					</li>
					<?php endforeach; ?>
				</ul>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
