<?php declare(strict_types = 1);

use RemoteDataBlocks\Example\Airtable\EldenRingMap\InteractivityStore;

$interactive_context = InteractivityStore::get_map_interactive_context( $block );

?>
<div
	<?php echo get_block_wrapper_attributes(); ?>
	<?php echo wp_interactivity_data_wp_context( $interactive_context ); ?>
></div>
