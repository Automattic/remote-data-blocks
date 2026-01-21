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

Replace `{{ Data source UUID }}` with the UUID you copied from the data source creation step above (step 7 in "Create the data source").

The query should append the zip code input variable to the data source endpoint, and define the output schema to map the API response fields to display in your block. Once registered, your block will be available in the block editor.

## Insert the block

Create or edit a page or post, then using the Block Inserter, search for your block by the title you used when registering it (e.g., "Zip Code").

After inserting the block, you'll be prompted to enter a zip code. Enter a valid US zip code (e.g., "90210") to see the location data appear in the block.

## Patterns and styling

You can use patterns to create a consistent, reusable layout for your remote data. You can read more about [patterns](../extending/block-patterns.md).

Remote data blocks can be styled using the block editor's style settings, `theme.json`, or custom stylesheets. See the [example child theme](https://github.com/Automattic/remote-data-blocks/tree/trunk/example/templates/theme) for more details.

## Code reference

You can also configure HTTP integrations with code. These integrations appear in the WordPress admin but can not be modified. You may wish to do this to have more control over the data source or because you have more advanced data processing needs.

This [example template](https://github.com/Automattic/remote-data-blocks/tree/trunk/example/templates/rest-api-block-from-ui-data-source) will replicate what we've done in this tutorial.
