# Create a remote data block using a data source defined in the UI

This page will walk you through registering a remote data block that loads data from a Zip code REST API. It will require you to commit code to a WordPress theme or plugin. If you have not yet installed and activated the Remote Data Blocks plugin, visit [Getting Started](https://remotedatablocks.com/getting-started/).

## The contract

Developers can use a UUID (v4) to define a "contract" between the remote data block integration they build and data sources defined in the Remote Data Blocks plugin settings screen.

## Create the data source

1. Go to the Settings > Remote Data Blocks in your WordPress admin.
2. Click on the "Connect new" button.
3. Choose "HTTP" from the dropdown menu as the data source type.
4. Fill in the following details:
   - Name: Zip Code API
   - Endpoint: https://api.zippopotam.us/us/
5. Save the data source and return the data source list.
6. In the actions column, click on the copy button to copy the data source's UUID to your clipboard.

## Register the block

In code, we'll define a query that uses the data source we just created using its UUID.

```php
<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\ZipCode;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Config\Query\HttpQuery;

function register_zipcode_block(): void {
    // Use the UUID you copied in the previous step.
	$zipcode_data_source = HttpDataSource::from_uuid( '0d8f9e74-5244-49b4-981b-e5374107aa5c' );

	if ( ! $zipcode_data_source instanceof HttpDataSource ) {
		return;
	}

	$zipcode_query = HttpQuery::from_array( [
		'data_source' => $zipcode_data_source,
		'endpoint' => function ( array $input_variables ) use ( $zipcode_data_source ): string {
			return $zipcode_data_source->get_endpoint() . $input_variables['zip_code'];
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
	] );

	register_remote_data_block( [
		'title' => 'Zip Code',
		'queries' => [
			'display' => $zipcode_query,
		],
	] );
}
add_action( 'init', __NAMESPACE__ . '\\register_zipcode_block' );
```
