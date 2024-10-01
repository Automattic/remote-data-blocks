# Block patterns

Patterns allow you to represent your remote data if different ways. By default, the plugin registers a unstyled block pattern that you can use out of the box. You can create additional patterns in the WordPress Dashboard or programmatically using the `register_remote_data_block_pattern` function.

Example:

```html
<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
	<!-- wp:heading {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"field":"title"}}}}} -->
	<h2 class="wp-block-heading"></h2>
	<!-- /wp:heading -->
	<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"field":"description"}}}}} -->
	<p></p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
```

```php
function register_your_block_pattern() {
    $block_name    = 'Your Custom Block';
    $block_pattern = file_get_contents( '/path/to/your-pattern.html' );

    register_remote_data_block_pattern( $block_name, 'Pattern Title', $block_pattern );
}
add_action( 'init', 'YourNamespace\\register_your_block_pattern', 10, 0 );
```
