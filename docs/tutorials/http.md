# Create a remote data block using an HTTP data source

This page will walk you through registering a remote data block that loads data from a Zip code REST API. It will require you to commit code to a WordPress theme or plugin.

## Create the data source

1. Go to Settings > Remote Data Blocks in your WordPress admin.
2. Click on the "Connect new" button.
3. Choose "HTTP" from the dropdown menu as the data source type.
4. Fill in the following details:
   - Data Source Name: Zip Code API
   - URL: https://api.zippopotam.us/us/
5. If your API requires authentication, enter those details. This API does not.
6. Save the data source and return the data source list.
7. In the Actions column, click the three-dot menu, then "Copy UUID" to copy the data source's UUID to your clipboard.

## Register the block

Next, define a query and register a block using the data source you just created. Add this code to your theme's `functions.php` file or a custom plugin, replacing `{{ Data source UUID }}` with the UUID you copied from the data source list.

```php
<?php

use RemoteDataBlocks\Config\DataSource\HttpDataSource;

function register_zip_code_remote_data_block(): void {
	$zip_code_data_source = HttpDataSource::from_uuid( '{{ Data source UUID }}' );

	if ( is_wp_error( $zip_code_data_source ) ) {
		return;
	}

	$zip_code_query = [
		'data_source' => $zip_code_data_source,
		'display_name' => 'Get location by Zip code',
		'endpoint' => function ( array $input_variables ) use ( $zip_code_data_source ): string {
			return $zip_code_data_source->get_endpoint() . $input_variables['zip_code'];
		},
		'input_schema' => [
			'zip_code' => [
				'name' => 'Zip Code',
				'type' => 'string',
			],
		],
		'output_schema' => [
			'is_collection' => false,
			'type' => [
				'zip_code' => [
					'name' => 'Zip Code',
					'path' => '$["post code"]',
					'type' => 'string',
				],
				'city' => [
					'name' => 'City',
					'path' => '$.places[0]["place name"]',
					'type' => 'string',
				],
				'state' => [
					'name' => 'State',
					'path' => '$.places[0].state',
					'type' => 'string',
				],
			],
		],
	];

	register_remote_data_block( [
		'title' => 'Zip Code',
		'render_query' => [
			'query' => $zip_code_query,
		],
	] );
}
add_action( 'init', 'register_zip_code_remote_data_block' );
```

This code:

1. Loads the data source by UUID using `HttpDataSource::from_uuid()`.
2. Defines a render query that accepts a zip code, appends it to the data source endpoint, and maps the response fields to block outputs.
3. Registers a "Zip Code" block that uses the render query.

For example, if the editor provides `90210`, the query requests `https://api.zippopotam.us/us/90210` and maps the `post code`, `place name`, and `state` fields from the API response.

## Insert the block

Create or edit a page or post, then search for "Zip Code" in the block inserter. After inserting the block, enter a valid US zip code, such as `90210`, to fetch and display location data.

## Patterns and styling

The plugin registers an unstyled block pattern for each remote data block. You can duplicate that default pattern in the Site Editor and associate a custom pattern with your block later. Read more in [Block patterns](../extending/block-patterns.md).

Remote data blocks can also be styled with the block editor's style settings, `theme.json`, or custom stylesheets.

## Code reference

The [Zip Code block example](https://github.com/Automattic/remote-data-blocks/tree/trunk/example/blocks/zip-code-block/zip-code-block.php) shows a similar block with the data source defined entirely in code.

The [REST API block from UI-created data source template](https://github.com/Automattic/remote-data-blocks/tree/trunk/example/templates/rest-api-block-from-ui-data-source) shows a larger template for APIs that need both render and selection queries.
