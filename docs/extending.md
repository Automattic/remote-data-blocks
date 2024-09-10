# Extending Remote Data Blocks: Developer Guide

Remote Data Blocks is designed to be extensible, allowing developers to create new data sources and queries. This guide will walk you through the process of extending the core plugin.

## 1. Creating a New Data Source

To create a new data source:

1. Create a new PHP class that extends the base datasource class.
2. Implement the required methods for fetching and processing data.

Example:

```php
namespace YourNamespace;

class YourCustomDatasource extends \RemoteDataBlocks\Queries\Datasource {
    public function fetch_data( $query_args ) {
        // Implement your data fetching logic here
    }

    public function process_results( $results ) {
        // Process and format the fetched data
    }
}
```

## 2. Creating a New Query

To create a new query:

1. Create a new PHP class that extends the base query class.
2. Implement the required methods for executing the query and formatting results.

Example:

```php
namespace YourNamespace;
class YourCustomQuery extends \RemoteDataBlocks\Queries\Query {
    public function execute($input) {
        // Implement your query execution logic here
    }

    public function format_results($results) {
        // Format the query results
    }
}
```

## 3. Registering Your New Block

To register your new block:

1. Create a new PHP file for registering your block.
2. Use the `ConfigurationLoader` class to register your block, queries, and patterns.

Example:

```php
namespace YourNamespace;

use RemoteDataBlocks\Editor\ConfigurationLoader;

function register_your_custom_block() {
    $block_name = 'Your Custom Block';
    $your_datasource = new YourCustomDatasource();
    $your_query = new YourCustomQuery($your_datasource);
    ConfigurationLoader::register_block($block_name, $your_query);
    ConfigurationLoader::register_list_query($block_name, $your_query);
    $block_pattern = file_get_contents(DIR . '/your-pattern.html');
    ConfigurationLoader::register_block_pattern($block_name, 'your-namespace/your-pattern', $block_pattern);
}
add_action('register_remote_data_blocks', 'YourNamespace\\register_your_custom_block');
```

## 4. Creating Block Patterns

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
