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
    public array $input_variables = [
        // Implement your input variables here
    ];

    public array $output_variables = [
        // Implement your output variables here
    ];

    public function get_endpoint( array $input_variables ): string {
        // Optionally implemented override of datasource endpoint logic here
        // eg, to add additional query parameters
    }
}
```

## 3. Register Your New Block

To register your new block:

1. Create a new PHP file for registering your block.
2. Use the `ConfigRegistry` class to register your block, queries, and patterns.

Example:

```php
use RemoteDataBlocks\Editor\ConfigRegistry;

function register_your_custom_block() {
    $block_name = 'Your Custom Block';
    $your_datasource = new YourCustomDatasource();
    $your_query = new YourCustomQuery( $your_datasource );
    ConfigRegistry::register_block( $block_name, $your_query );
    ConfigRegistry::register_list_query( $block_name, $your_query );
    $block_pattern = file_get_contents( __DIR__ . '/your-pattern.html' );
    ConfigRegistry::register_block_pattern( $block_name, 'your-namespace/your-pattern', $block_pattern );
}
add_action( 'init', 'YourNamespace\\register_your_custom_block' );
```

## 4. Create Block Patterns

Create HTML patterns for your block to define how the data should be displayed. Use the Remote Data Blocks binding syntax to connect data to your pattern.

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
