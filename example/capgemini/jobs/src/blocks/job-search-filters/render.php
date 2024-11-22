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
	<template data-wp-each--group="state.filterGroups">
		<h3 data-wp-text="context.group.title"></h3>
		<ul>
			<template data-wp-each--filter="context.group.filters">
				<li>
					<input
						type="checkbox"
						data-wp-bind--id="context.filter.id"
						data-wp-bind--value="content.filter.title"
						data-wp-on--change="actions.toggleFilter"
					>
					<label
						data-wp-bind--for="context.filter.id"
						data-wp-text="context.filter.title"
					></label>
					<span
						class="count"
						data-wp-text="context.filter.count"
					></span>
				</li>
			</template>
		</ul>
	</template>
</div>
