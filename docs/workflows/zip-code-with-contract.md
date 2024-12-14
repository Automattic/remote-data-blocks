# Create a zip code remote data block

This page will walk you through building [Zippopotam.us](https://zippopotam.us/) queries, registering a remote data block to display zip code information, and then connecting a data source later. It will require you to commit code to a WordPress theme or plugin. If you have not yet installed and activated the Remote Data Blocks plugin, visit [Getting Started](https://remotedatablocks.com/getting-started/).

## The contract

Developers can code a version 4 UUID to define a "contract" between the remote data block integration they build and the admins managing the Remote Data Blocks settings in WordPress.

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
		return $this->get_data_source()->get_endpoint() . $input_variables['zip_code'];
	}
}
```

This query describes what input it needs (a zip code) and the data it returns (zip code, city, and state). The get_endpoint method builds the URL for the API request using the provided zip code.

## Register the block

Now that we have a query defined, we can write the code to register a WordPress block to display the remote data. Here's the example:

```php
<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\ZipCode;

use RemoteDataBlocks\Integrations\GenericHttp\GenericHttpDataSource;
use RemoteDataBlocks\Logging\LoggerManager;

require_once __DIR__ . '/inc/queries/class-get-zip-code-query.php';

function register_zipcode_block() {
	$zipcode_data_source = GenericHttpDataSource::from_uuid( '0d8f9e74-5244-49b4-981b-e5374107aa5c' );

	if ( ! $zipcode_data_source instanceof GenericHttpDataSource ) {
		LoggerManager::instance()->debug( 'Zip Code data source not found' );
		return;
	}

	$zipcode_query = new GetZipCodeQuery( $zipcode_data_source );

	register_remote_data_block( 'Zip Code', $zipcode_query );
}
add_action( 'init', __NAMESPACE__ . '\\register_zipcode_block' );

```

Note the `0d8f9e74-5244-49b4-981b-e5374107aa5c` UUID in the `GenericHttpDataSource::from_uuid` call. That's the "contract" in our implementation.

We're done!

## Later on

An admin can seperately set up the data source via the following steps:

1. Go to the Remote Data Blocks settings page in your WordPress admin area.
2. Click on the "Add" menu button.
3. Choose "HTTP" from the dropdown menu as the data source type.
4. Fill in the following details:
   - Name: Zip Code API
   - Endpoint: https://api.zippopotam.us/us/
5. Save the data source.
6. Click on the edit icon for the newly saved data source.
7. Click on the settings icon next to "Edit HTTP Data Source".
8. Update the UUID to match the one defined in the code or vice versa.

The UUID _must_ match the UUID defined by the developer in the previous section when creating the data source.
