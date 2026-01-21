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

Now we'll write code to define a query and register a block using the data source we just created. Add this code to your theme's `functions.php` file or a custom plugin:

```php
<?php

use RemoteDataBlocks\Config\DataSource\HttpDataSource;

function register_zip_code_remote_data_block(): void {
	// Load the data source we created in the UI by its UUID
	$zip_code_data_source = HttpDataSource::from_uuid( '{{ Data source UUID }}' );

	// Define the query
	$zip_code_query = [
		'data_source' => $zip_code_data_source,
		'display_name' => 'Get location by Zip code',
		// Provide a callable (closure) to dynamically generate the endpoint using
		// the base endpoint from the data source and the input variables.
		'endpoint' => function ( array $input_variables ) use ( $zip_code_data_source ): string {
			// Validate and sanitize the zip code input
			$zip_code = preg_replace( '/[^0-9-]/', '', $input_variables['zip_code'] ?? '' );
			return $zip_code_data_source['endpoint'] . $zip_code;
		},
		'input_schema' => [
			'zip_code' => [
				'name' => 'Zip Code',
				'type' => 'string',
			],
		],
		'output_schema' => [
			'is_collection' => false, // This query returns a single record.
			'type' => [
				'zip_code' => [
					'name' => 'Zip Code',
					'path' => '$["post code"]', // JSONPath syntax: brackets and quotes for properties with spaces.
					'type' => 'string',
				],
				'city' => [
					'name' => 'City',
					'path' => '$.places[0]["place name"]', // JSONPath syntax: brackets and quotes for properties with spaces.
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

	// Register the remote data block
	register_remote_data_block( [
		'title' => 'Zip Code',
		'render_query' => [
			'query' => $zip_code_query,
		],
	] );
}
add_action( 'init', 'register_zip_code_remote_data_block' );
```

Replace `{{ Data source UUID }}` with the UUID you copied from the data source creation step above (step 7 in "Create the data source").

This code does the following:

1. **Loads the data source** by UUID using `HttpDataSource::from_uuid()`
2. **Defines a query** that:
   - Takes a zip code as input
   - Appends it to the data source endpoint (e.g., `https://api.zippopotam.us/us/90210`)
   - Maps the API response fields to block outputs (zip code, city, state)
3. **Registers the block** with the title "Zip Code" and associates it with the query

Once you save this code and reload your WordPress admin, the block will be available in the block editor.

## Insert the block

Create or edit a page or post, then using the Block Inserter, search for your block by the title you used when registering it (e.g., "Zip Code").

After inserting the block, you'll be prompted to enter a zip code. Enter a valid US zip code (e.g., "90210") to see the location data appear in the block.

## Patterns and styling

You can use patterns to create a consistent, reusable layout for your remote data. You can read more about [patterns](../extending/block-patterns.md).

Remote data blocks can be styled using the block editor's style settings, `theme.json`, or custom stylesheets. See the [example child theme](https://github.com/Automattic/remote-data-blocks/tree/trunk/example/templates/theme) for more details.

## Code reference

You can also configure HTTP integrations with code. These integrations appear in the WordPress admin but can not be modified. You may wish to do this to have more control over the data source or because you have more advanced data processing needs.

This [example template](https://github.com/Automattic/remote-data-blocks/tree/trunk/example/templates/rest-api-block-from-ui-data-source) will replicate what we've done in this tutorial.
