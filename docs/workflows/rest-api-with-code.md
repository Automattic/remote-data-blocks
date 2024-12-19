# Create a remote data block using code

This page will walk you through registering a remote data block that loads data from a Zip code REST API. It will require you to commit code to a WordPress theme or plugin. If you have not yet installed and activated the Remote Data Blocks plugin, visit [Getting Started](https://remotedatablocks.com/getting-started/).

Unlike the [UI-based example](rest-api.md), this example only uses code to define both the data source and query.

## Register the block

```php
<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\ZipCode;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Config\Query\HttpQuery;

function register_zipcode_block(): void {
	$zipcode_data_source = HttpDataSource::from_array( [
		'service_config' => [
			'__version' => 1,
			'display_name' => 'Zip Code API',
			'endpoint' => 'https://api.zippopotam.us/us/',
		],
	] );

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
