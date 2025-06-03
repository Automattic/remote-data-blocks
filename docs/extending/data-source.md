# Data source

A data source defines the basic reusable properties of an API and is used by a [query](query.md) to reduce duplicative code. It also helps define how your data source looks in the WordPress admin.

Simple data sources can be configured via the plugin's settings screen, while others may require custom PHP code.

## Example

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
