# Block patterns

Patterns allow you to represent your remote data if different ways. By default, the plugin registers a unstyled block pattern that you can use out of the box. You can create additional patterns in the WordPress Dashboard or programmatically by passing a `patterns` property to your block options.

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
register_remote_data_block( [
	'title' => 'My Remote Data Block',
	'queries' => [ /* ... */ ],
	'patterns' => [
		[
			'title' => 'My Pattern',
			'content' => file_get_contents( '/path/to/your-pattern.html' ),
		],
	],
] );
```
