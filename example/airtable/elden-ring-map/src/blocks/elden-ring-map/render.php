<?php

use RemoteDataBlocks\Example\Airtable\EldenRingMap\InteractivityStore;

$interactive_context = InteractivityStore::get_map_interactive_context( $block );
$public_store_name   = InteractivityStore::get_store_name();

?>
<div
	data-wp-interactive="<?php echo esc_attr( $public_store_name ); ?>"
	<?php echo get_block_wrapper_attributes(); ?>
	<?php echo wp_interactivity_data_wp_context( $interactive_context ); ?>
>
</div>
