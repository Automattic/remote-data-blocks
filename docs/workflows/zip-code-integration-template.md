# Integration templates

Developers can [extend](index.md) the plugin to meet uses cases that require custom logic. Often, the full details are known up front to the developer, in which case they can simply write a complete implementation.

However, sometimes the developer may not know the final data source details.

Integration templates bridge the gap between developers and WordPress instance admins when implementing custom data fetching solutions. These templates support scenarios where developers need to create custom blocks that interact with third-party APIs, but may not have full access to authentication details managed by other teams.

## The contract

Developers can code a "slug" to define a "contract" between the integration template they write and the admins managing the Remote Data Blocks settings in WordPress.

This page will walk through creating an integration template for displaying information about a given zip code to demonstrate the process.

## Create a Zip Code remote data block

## Define a query

First, we'll define a query that describes the data to extract from the Zippopotam.us zip code API. We create a class that extends `HttpQueryContext`:

```php
<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\ZipCode;

use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;

class GetZipCodeQuery extends HttpQueryContext {
	public function get_input_schema(): array {
		return [
			'zip_code' => [
				'name' => 'Zip Code',
				'type' => 'string',
			],
		];
	}

	public function get_output_schema(): array {
		return [
			'is_collection' => false,
			'mappings'      => [
				'zip_code' => [
					'name' => 'Zip Code',
					'path' => '$["post code"]',
					'type' => 'string',
				],
				'city'     => [
					'name' => 'City',
					'path' => '$.places[0]["place name"]',
					'type' => 'string',
				],
				'state'    => [
					'name' => 'State',
					'path' => '$.places[0].state',
					'type' => 'string',
				],
			],
		];
	}

	public function get_endpoint( $input_variables ): string {
		return $this->get_datasource()->get_endpoint() . $input_variables['zip_code'];
	}
}
```

This query describes what input it needs (a zip code) and the data it returns (zip code, city, and state). The get_endpoint method builds the URL for the API request using the provided zip code.

## Register the block

Now that we have a query defined, we can write the code to register a WordPress block to display the remote data. Here's the example:

```php
<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\ZipCode;

use RemoteDataBlocks\Data\DataSourceRepository;
use RemoteDataBlocks\Integrations\GenericHttp\GenericHttpDatasource;
use RemoteDataBlocks\Logging\LoggerManager;

require_once __DIR__ . '/inc/queries/class-get-zip-code-query.php';

function register_zipcode_block() {
	$zipcode_datasource = DataSourceRepository::get( 'zip-code' );

	if ( ! $zipcode_datasource instanceof GenericHttpDatasource ) {
		LoggerManager::instance()->debug( 'Zip Code datasource not found' );
		return;
	}

	$zipcode_query = new GetZipCodeQuery( $zipcode_datasource );

	register_remote_data_block( 'Zip Code', $zipcode_query );
}
add_action( 'init', __NAMESPACE__ . '\\register_zipcode_block' );
```

Note the `zip-code` slug in the `DataSourceRepository::get` call. That's our "contract" in our integration template.

We're done!

## Later on

An admin can set up the data source via the following steps:

1. Go to the Remote Data Blocks settings page in your WordPress admin area.
2. Click on "Add Data Source".
3. Choose "Generic HTTP" as the data source type.
4. Fill in the following details:
   - Name: Zip Code API
   - Slug: zip-code
   - Endpoint: https://api.zippopotam.us/us/
5. Save the data source.

The slug _must_ match the slug defined by the developer in the previous section when creating the datasource.
