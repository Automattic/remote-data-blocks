# Data source

A data source defines the basic reusable properties of an API and is used by a [query](query.md) to reduce duplicative code. It also helps define how your data source looks in the WordPress admin.

## Built-in services

The plugin provides built-in support for a small number of services: Airtable, Google Sheets, and Shopify. Data sources for built-in services can be configured via the plugin's settings screen and offer automatic query and block registration. They can also be configured via code using dedicated classes with simplified configuration:

```php
$shopify_data_source = ShopifyDataSource::from_array( [
	'service_config' => [
		'__version' => 1,
		'access_token' => '{{ Access Token }}',
		'display_name' => '{{ Shopify Store Display Name }}',
		'store_name' => '{{ store-name.myshopify.com }}',
	],
] );
```

See the [example templates](https://github.com/Automattic/remote-data-blocks/tree/trunk/example/templates) for additional example code. Simple HTTP APIs can also be configured via the plugin's settings screen, but do not offer automatic query and block registration.

### Load by UUID

A data source that has been defined in the plugin settings screen can be loaded by its UUID using the `HttpDataSource::from_uuid()` static method. The UUID is provided via the actions (three-dot) menu. This approach allows you to write code to define [queries](query.md) and [register blocks](block-registration.md) to complete your integration.

```php
$data_source = HttpDataSource::from_uuid( '{{ Data source UUID }}' );

/* Additional code to use the data source in queries and block registration */
```

Unsupported data sources, as well as data sources that require customization not offered in the UI, must be defined in code.

## Code example

Here's an example of a data source configuration for an HTTP API:

```php
$data_source = [
	'display_name' => 'Example API',
	'endpoint' => 'https://api.example.com/',
	'request_headers' => [
		'Content-Type' => 'application/json',
		'X-Api-Key' => constant( 'MY_API_KEY_CONSTANT' ),
	],
];
```

And here is an example of a data source that was defined in the plugin settings screen, loaded by its UUID:

```php
$data_source = HttpDataSource::from_uuid( '{{ Data source UUID }}' );
```

## Configuration

### display_name: string (required)

The display name is used in the UI to identify your data source.

### endpoint: string (required)

This is the default or base endpoint for the data source. [Queries](query.md) that use a data source can override or append paths to its endpoint.

### image_url: string

An optional image URL can be used in the UI to help identify your data source.

### request_headers: array

An associative array of headers that will be sent with each HTTP request. Queries that use a data source can override or append headers.

When providing authentication credentials, take care to avoid committing them to code repositories. We strongly recommend using environment variables or secure storage.

### Next steps

After defining a data source in code, you can use it in a [query](query.md) to define how data is retrieved.
