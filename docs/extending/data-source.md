# Data source

A data source defines the basic reusable properties of an API and is used by a [query](query.md) to reduce duplicative code. It also helps define how your data source looks in the WordPress admin.

## Example

Most HTTP-powered APIs can be represented by defining a array that be provided to `HttpDataSource::from_array()`. Here's an example of a data source for an example HTTP API:

```php
$data_source = HttpDataSource::from_array( [
	'version' => 1,
	'display_name' => 'Example API',
	'endpoint' => 'https://api.example.com/',
	'request_headers' => [
		'Content-Type' => 'application/json',
		'X-Api-Key' => constant( 'MY_API_KEY_CONSTANT' ),
	],
] );
```

## HttpDataSource configuration

### **version**: number (required)

There is no built-in versioning logic, but a version number is required for best practice reasons. Changes to the data source could significantly affect [queries](query.md). Checking the data source version is a sensible defensive practice.

### display_name: string (required)

The display name is used in the UI to identify your data source.

### endpoint: string (required)

This is the default or base endpoint for the data source. [Queries](query.md) that use a data source can override or append paths to its endpoint.

### image_url: string

An optional image URL can be used in the UI to help identify your data source.

### request_headers: array

An associative array of headers that will be sent with each HTTP request. Queries that use a data source can override or append headers.

When providing authentication credentials, take care to avoid committing them to code repositories. We strongly recommend using environment variables or secure storage.

## Custom data sources

It's usually not necessary to extend `HttpDataSource`, but you can do so if you need to add custom behavior. For APIs that use non-HTTP transports, you could implement `DataSourceInterface` and provide methods that define reusable properties of your API. The actual implementation of your transport will need to be provided by a [custom query runner](./query-runner.md).

Here is a theoretical example of a data source for a WebDAV server:

```php
class WebDavFilesDataSource implements DataSourceInterface {
    public function get_display_name(): string {
        return 'My WebDAV Files';
    }

    public function get_image_url(): string {
        return 'https://example.com/webdav-icon.png';
    }

    public function get_webdav_root(): string {
        return 'webdavs://webdav.example.com/';
    }
}
```
