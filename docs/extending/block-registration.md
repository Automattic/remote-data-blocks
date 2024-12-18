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

	$display_query = HttpQuery::from_array( [
		'display_name' => 'Example Query',
		'data_source' => $data_source,
		'output_schema' => [
			'type' => [
				'id => [
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
		'queries' => [
			'display' => $display_query,
		],
	] );
}
add_action( 'init', 'YourNamespace\\register_your_custom_block', 10, 0 );
```
