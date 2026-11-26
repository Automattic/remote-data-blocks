<?php declare(strict_types = 1);

use RemoteDataBlocks\Editor\DataBinding\BlockBindings;

// $attributes (array): The block attributes.
// $content (string): The block default content.
// $block (WP_Block): The block instance.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$binding = $block->attributes['metadata']['bindings']['content'] ?? [];
$args = $binding['args'] ?? null;

?>

<div <?php echo wp_kses_data( get_block_wrapper_attributes() ); ?>>
	<?php
	$binding_value = null;
	if ( ( $binding['source'] ?? null ) === BlockBindings::$binding_source && is_array( $args ) && is_string( $args['field'] ?? null ) ) {
		$binding_value = BlockBindings::get_value( $args, $block, 'content' );
	}
	echo wp_kses_post( $binding_value ?? $content );
	?>
</div>
