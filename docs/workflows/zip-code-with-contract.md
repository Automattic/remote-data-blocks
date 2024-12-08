# Create a zip code remote data block

This page will walk you through building [Zippopotam.us](https://zippopotam.us/) queries, registering a remote data block to display zip code information, and then connecting a data source later. It will require you to commit code to a WordPress theme or plugin. If you have not yet installed and activated the Remote Data Blocks plugin, visit [Getting Started](https://remotedatablocks.com/getting-started/).

## The contract

Developers can code a "slug" to define a "contract" between the remote data block integration they build and the admins managing the Remote Data Blocks settings in WordPress.

## Register the block

First, we'll define a query that describes the data to extract from the Zippopotam.us zip code API. We create a query instance using `HttpQueryContext::from_array`. Then, we can write the code to register a WordPress block to display the remote data. Here's the example:

```php
<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\ZipCode;

use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;
use RemoteDataBlocks\Integrations\GenericHttp\GenericHttpDataSource;
use RemoteDataBlocks\Logging\LoggerManager;

function register_zipcode_block(): void {
	$zipcode_data_source = GenericHttpDataSource::from_slug( 'zip-code' );

	if ( ! $zipcode_data_source instanceof GenericHttpDataSource ) {
		LoggerManager::instance()->debug( 'Zip Code data source not found' );
		return;
	}

	$zipcode_query = HttpQueryContext::from_array( [
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

	register_remote_data_block( 'Zip Code', $zipcode_query );
}
add_action( 'init', __NAMESPACE__ . '\\register_zipcode_block' );
```

Note the `zip-code` slug in the `GenericHttpDataSource::from_slug` call. That's the "contract" in our implementation.

We're done!

## Later on

An admin can seperately set up the data source via the following steps:

1. Go to the Remote Data Blocks settings page in your WordPress admin area.
2. Click on "Add Data Source".
3. Choose "Generic HTTP" as the data source type.
4. Fill in the following details:
   - Name: Zip Code API
   - Slug: zip-code
   - Endpoint: https://api.zippopotam.us/us/
5. Save the data source.

The slug _must_ match the slug defined by the developer in the previous section when creating the data source.
