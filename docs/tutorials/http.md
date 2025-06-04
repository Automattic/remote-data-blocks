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

In code, we'll define a query using the data source we just created. Follow the [Zip code block example](https://github.com/Automattic/remote-data-blocks/tree/trunk/example/blocks/zip-code-block/zip-code-block.php), but remove the data source definition. In its place, use this code to load the data source we just created by its UUID:

```php
$data_source = HttpDataSource::from_uuid( '{{ Data source UUID }}' );
```
