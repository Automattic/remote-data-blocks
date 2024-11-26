# Core concepts

Remote Data Blocks allows you to integrate remote data into posts, pages, patterns, or anywhere else on your site where you use the block editor. This guide will help you understand the core concepts of the plugin and how they work.

## Remote data

**Remote data** refers to data that is fetched from an external source, such as an API or a third-party service. This might be a product in your Shopify store, data in an Airtable or Google Sheet, or a file in a GitHub repository. Remote data is usually fetched via HTTP requests but you can [extend the plugin](../extending/index.md) to support other transports.

## Remote data block

A **remote data block** is a custom block that fetches and displays data from a specific remote source. Each remote data block has a unique name and provides a specific kind of data.

For example, you might have a remote data block named "Shopify Product" that fetches a product from your Shopify store and displays the product's name, description, price, and image. Or, you might have a remote data block named "Conference event" that displays rows from an Airtable and displays the event's name, location, and type. It might look something like this:

[image tk]

Remote data blocks are **container blocks** that contain other blocks and provide remote data to them. You retain full control over the layout, design, and content. You can leverage patterns to enable consistent styling and workflows, and you can customize the block's appearance using the block editor or `theme.json`.

Remote data blocks are custom blocks, but they are created and registered by our plugin and don't require custom block development. Remote data is loaded via [the block bindings API](https://make.wordpress.org/core/2024/03/06/new-feature-the-block-bindings-api/).

## Data sources and queries

Each remote data block is associated with a **data source** and a **query** that defines how data is fetched, processed, and displayed. Simple data sources and queries can be configured via the plugin's settings screen, while others may require custom PHP code (see [extending](../extending/index.md)).

## Data fetching

Data fetching is handled by the plugin and wraps `wp_remote_request`. When a request to your site resolves to one or more remote data blocks, the remote data will be fetched and potentially cached by our plugin. Multiple requests for the same data will be deduped, even if the requests are not cacheable.

### Caching

The plugin offers a caching layer for optimal performance and to help avoid rate limiting from remote data sources. If your WordPress environment has configured a [persistent object cache](https://developer.wordpress.org/reference/classes/wp_object_cache/#persistent-cache-plugins), it will be used. Otherwise, the plugin will utilize in-memory (per-request) caching. Deploying to production without a persistent object cache is not recommended.

The default TTL for all cache objects is 60 seconds, but can be adjusted by extending the query class and [overriding the `get_cache_ttl` method](../extending/query.md#get_cache_ttl).

## Theming

Remote data blocks can be styled using the block editor's style settings, `theme.json`, or custom stylesheets. See the [example child theme](https://github.com/Automattic/remote-data-blocks/tree/trunk/example/theme) for more details.

## Patterns

Since remote data blocks are container blocks, you can use patterns to create reusable layouts and to streamline your workflows. Patterns can be associated with a remote data block in the "Pattern" settings in the sidebar of the pattern editor. Once associated with a remote data block, patterns will appear in the pattern selection modal. The plugin supports both synced and unsynced patterns.

## Technical concepts

If you're a developer and want to understand the internals of Remote Data Blocks so that you can extend its functionality, head over the [extending guide](../extending/index.md).
