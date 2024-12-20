# Data source

A data source defines basic reusable properties of an API and is used by a [query](query.md) to reduce boilerplate. It allows helps this plugin represent your data source in the plugin settings screen and other UI.

## Example

Most HTTP-powered APIs can be represented by defining a class that extends `HttpDataSource`. Here's an example of a data source for an example HTTP API:

```php
$data_source = HttpDataSource::from_array( [
	'service_config' => [
		'__version' => 1,
		'display_name' => 'Example API',
		'endpoint' => 'https://api.example.com/',
		'request_headers' => [
			'Content-Type' => 'application/json',
			'X-Api-Key': MY_API_KEY_CONSTANT,
		],
	],
] );
```

The configuration array passed to `from_array` is very flexible, so it's usually not necessary to extend `HttpDataSource`, but you can do so if you need to add custom behavior.

## Custom data sources

For APIs that use non-HTTP transports, you can also implement `DataSourceInterface` and provide methods that define reusable properties of your API. The actual implementation of your transport will need to be provided by a [custom query runner](./query-runner.md).

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
