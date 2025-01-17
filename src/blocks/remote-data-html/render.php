<?php


// $attributes (array): The block attributes.
// $content (string): The block default content.
// $block (WP_Block): The block instance.

$context = $block->context;
$remote_data_context = $context['remote-data-blocks/remoteData'] ?? [];
$remote_data_result = $remote_data_context['results'][0] ?? [];
$remote_data_html = $remote_data_result['htmlContent'] ?? '';

?>

<div <?php echo get_block_wrapper_attributes(); ?>>
	<?php echo $remote_data_html; ?>
</div>
