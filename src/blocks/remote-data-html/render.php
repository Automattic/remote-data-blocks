<?php declare(strict_types = 1);

use RemoteDataBlocks\Editor\DataBinding\BlockBindings;

// $attributes (array): The block attributes.
// $content (string): The block default content.
// $block (WP_Block): The block instance.

$source_args = $block->attributes['metadata']['bindings']['content']['args'] ?? [];

?>

<div <?php echo get_block_wrapper_attributes(); ?>>
	<?php
	/**
	 * @psalm-suppress UndefinedGlobalVariable
	 * $block is provided by WordPress for rendering, see header comments
	 */
	$binding_value = BlockBindings::get_value( $source_args, $block );

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- This output is specifically for raw HTML.
	echo $binding_value;
	?>
</div>
