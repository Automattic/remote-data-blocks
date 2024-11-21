<?php declare(strict_types = 1);

use RemoteDataBlocks\Example\Capgemini\Jobs\InteractivityStore;

$public_store_name = InteractivityStore::get_store_name();

wp_interactivity_state( $public_store_name, InteractivityStore::get_initial_state() );

?>
<div
	data-wp-interactive="<?php echo esc_attr( $public_store_name ); ?>"
	<?php echo get_block_wrapper_attributes(); ?>
>
	<fieldset>
		<button data-wp-on-async--click="actions.search">Search</button>
	</fieldset>
</div>
