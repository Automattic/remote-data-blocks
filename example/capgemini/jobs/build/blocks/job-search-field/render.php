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
	<fieldset>
		<legend>Search</legend>
		<input type="text" placeholder="Search" data-wp-on--change="actions.updateSearchTerms" data-wp-on--keydown="actions.updateSearchTerms" value="<?php echo esc_attr( $initial_state['searchTerms'] ?? '' ); ?>" />
	</fieldset>
</div>
