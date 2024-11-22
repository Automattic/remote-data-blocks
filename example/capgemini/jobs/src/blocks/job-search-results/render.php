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
	<ul>
		<template data-wp-each--job="state.jobs">
			<li data-wp-text="context.job.title"></li>
		</template>
		<?php foreach ( $initial_state['jobs'] ?? [] as $job ) : ?>
		<li data-wp-each-child><?php echo esc_html( $job['title'] ); ?></li>
		<?php endforeach; ?>
	</ul>
</div>
