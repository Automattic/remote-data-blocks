# Extending

Remote Data Blocks is designed to be extensible, allowing developers to create new data sources and queries. This guide will walk you through the process of extending the core plugin.

## 1. Create a New Data Source

To create a new data source:

1. Create a new PHP class that extends the `HttpDatasource` class.
2. Implement the required methods for fetching and processing data.

Example:

```php
class YourCustomDatasource extends HttpDatasource {
	public function get_endpoint(): string {
        // Implement your endpoint logic here
	}

	public function get_request_headers(): array {
        // Implement your headers logic here
	}
}
```

## 2. Create a New Query

To create a new query:

1. Create a new PHP class that extends the `HttpQueryContext` class.
2. Implement the required input and output variables for executing the query.
3. Optionally implement overrides of base class methods if needed.

Example:

```php
class YourCustomQuery extends HttpQueryContext {
    public function define_input_variables(): array {
		return [
			// Define your input variables here
		];
	}

    public function define_output_variables(): array {
		return [
			// Define your output variables here
		];
	}

    public function get_endpoint( array $input_variables ): string {
        // Optionally implemented override of datasource endpoint logic here
        // eg, to add additional query parameters
    }
}
```

## 3. Register Your New Block

To register your new block:

1. Create a new PHP file for registering your block.
2. Use the `register_remote_data_block` function to register your block.

Example:

```php
function register_your_custom_block() {
    $block_name      = 'Your Custom Block';
    $your_datasource = new YourCustomDatasource();
    $your_query      = new YourCustomQuery( $your_datasource );

    register_remote_data_block( $block_name, $your_query );
}
add_action( 'init', 'YourNamespace\\register_your_custom_block' );
```

## 4. Create Block Patterns (optional)

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
    $block_pattern = file_get_contents( **DIR** . '/your-pattern.html' );

    register_remote_data_block_pattern( $block_name, 'Pattern Title', $block_pattern );
}
add_action( 'init', 'YourNamespace\\register_your_block_pattern' );
```
