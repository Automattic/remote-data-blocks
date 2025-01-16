# Block registration

Use the `register_remote_data_block` function to register your block and associate it with your query and data source. This example:

1. Creates a data source
2. Associates the data source with a query
3. Defines the output schema of a query, which tells the plugin how to map the query response to blocks.
4. Registers a remote data block.

```php
function register_your_custom_block() {
	$data_source = HttpDataSource::from_array( [
		'service_config' => [
			'__version' => 1,
			'display_name' => 'Example API',
			'endpoint' => 'https://api.example.com/',
		],
	] );

	$render_query = HttpQuery::from_array( [
		'display_name' => 'Example Query',
		'data_source' => $data_source,
		'output_schema' => [
			'type' => [
				'id' => [
					'name' => 'ID',
					'path' => '$.id',
					'type' => 'id',
				],
				'title' => [
					'name' => 'Title',
					'path' => '$.title',
					'type' => 'string',
				],
			],
		],
	] );

	register_remote_data_block( [
		'title' => 'My Block',
		'render_query' => [
			'query' => $render_query,
		],
	] );
}
add_action( 'init', 'YourNamespace\\register_your_custom_block', 10, 0 );
```

## Configuration options

### `title`: string (required)

The human-friendly name of the block. It is also used to construct the block's name; a title of "My Block" will result in a block name of `remote-data-blocks/my-block`.

### `render_query`: array (required)

The render query is executed when the block is rendered and fetches the data that will be provided to block bindings. It is an array with the following properties:

- `query`: An instance of `QueryInterface` that fetches the data.
- `loop`: A boolean that determines if the query returns a collection of data. If `true`, the block will be rendered for each item in the collection.

### `selection_queries`: array (optional)

Selection queries are used to select or curate remote data in the block editor. For example, you may wish to load a list of products and select one for render, or you may want to allow a user to search for a specific item. Selection queries are an array of objects with the following properties:

- `display_name`: A human-friendly name for the selection query.
- `query`: An instance of `QueryInterface` that fetches the data.
- `type`: A string that determines the type of selection query. Accepted values are currently `list` or `search`.

Example:

```php
'selection_queries' => [
    [
        'display_name' => 'Select a product',
        'query' => $list_products_query,
        'type' => 'list',
    ],
    [
        'display_name' => 'Search for a product',
        'query' => $search_products_query,
        'type' => 'search',
    ],
],
```

### `overrides`: array (optional)

[Overrides](./overrides.md) are used to customize the behavior of the block on a per-block basis.

### `patterns`: array (optional)

[Block patterns](./block-patterns.md) allow you to customize the display of your remote data.
