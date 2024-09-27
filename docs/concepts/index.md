# Core concepts

Remote Data Blocks allows you to integrate remote data into posts, pages, patterns, or anywhere else on your site where you use the block editor. This guide will help you understand the core concepts of the plugin and how they work.

## Remote data

**Remote data** refers to data that is fetched from an external source, such as an API or a third-party service. This might be a product in your Shopify store, data in an Airtable or Google Sheet, or a file in a GitHub repository. Most commonly, remote data is fetched via HTTP requests but you can [extend the plugin](extending.md) to support other transports.

## Remote data block

A **remote data block** is a custom block that fetches and displays data from a specific remote source. Each remote data block has a unique name and provides a specific kind of data.

For example, you might have a remote data block named "Shopify Product" that fetches a product from your Shopify store and displays the product's name, description, price, and image. Or, you might have a remote data block named "Conference Sponsor" that displays rows from a Google Sheet and displays the sponsor's name, logo, and website. It might look something like this:

[image tk]

Remote data blocks are **container blocks** that contain other blocks and provide remote data to them. You retain full control over the layout, design, and content. You can leverage patterns to enable consistent styling and workflows, and you can customize the block's appearance using the block editor or `theme.json`.

While they are custom blocks, remote data blocks are created and registered by our plugin and don't require custom block development.

## Data sources and queries

Each remote data block is associated with a **data source** and a **query** that defines how data is fetched, processed, and displayed. Simple data sources and queries can be configured via the plugin's settings screen, while others may require custom PHP code (see [extending](extending.md)).

## Data fetching

## Caching

## Real-time updates

## Theming

## Technical concepts

If you're a developer and want to understand the internals of Remote Data Blocks so that you can extend its functionality, head over the [extending guide](extending.md).
