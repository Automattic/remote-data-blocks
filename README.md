# Remote Data Blocks

Remote Data Blocks is a WordPress plugin that brings an assortment of flexible, data-enrichable WordPress blocks into Gutenberg. These blocks make it easy for authors to mix local WordPress data, remote data, and layouts in the block editor.

## Setup

1. Download the Remote Data Blocks plugin zip file.
2. Log in to your WordPress admin panel.
3. Navigate to Plugins > Add New.
4. Click the "Upload Plugin" button at the top of the page.
5. Choose the downloaded zip file and click "Install Now".
6. After installation, click "Activate Plugin".

## How to Use

1. In the WordPress editor, add a new block by clicking the "+" icon.
2. Search for "remote" in the block inserter.
3. Choose from the available blocks, such as:

   - Airtable Event
   - Elden Ring Map
   - Art Institute of Chicago
   - Zip Code
   - Shopify Product

4. Configure the block settings in the sidebar:

   - For data-driven blocks, use the search or list panels to select specific items.
   - Adjust any available display options.

5. Some blocks may require additional setup:

   - For Airtable blocks, ensure you have the correct API key and base ID.
   - For Shopify blocks, configure your store URL and access token.

6. Use the blocks to display dynamic content from various sources directly in your posts or pages.

7. Developers: extend the plugin by creating new datasources and queries. See the example files in the `example` directory for reference.

## Contributing

For WP monolith:

```
npm install && npm run start:monolith
```

For WP backend + WP Components w/Next.js frontend dev environment, make sure the [wp-components repo](https://github.com/Automattic/wp-components) is at peer level on your file system and then run from root here:

```
npm install && npm run start:decoupled
```
