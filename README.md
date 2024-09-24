# Remote Data Blocks

Remote Data Blocks is a WordPress plugin that brings an assortment of flexible, data-enrichable WordPress blocks into Gutenberg. These blocks make it easy for authors to mix local WordPress data, remote data, and layouts in the block editor.

## Setup

_Required: PHP 8.1+, WordPress 6.6+_

1. Download the [Remote Data Blocks plugin zip file](https://github.com/Automattic/remote-data-blocks/releases/latest/download/remote-data-blocks.zip).
2. Log in to your WordPress admin panel.
3. Navigate to Plugins > Add New.
4. Click the "Upload Plugin" button at the top of the page.
5. Choose the downloaded zip file and click "Install Now".
6. After installation, click "Activate Plugin".

## How to Use

1. In the WordPress editor, add a new block by clicking the "+" icon.
2. Search for "remote" in the block inserter.
3. Choose from the available blocks, such as:

   - Airtable Events
   - Art Institute of Chicago
   - GitHub Files
   - Zip Code Information
   - Shopify Products

4. Configure the block settings in the sidebar:

   - For data-driven blocks, use the search or list panels to select specific items.
   - Adjust any available display options.

5. Some blocks may require additional setup:

   - For Airtable blocks, ensure you have the correct API key and base ID.
   - For Shopify blocks, configure your store URL and access token.

6. Use the blocks to display dynamic content from various sources directly in your posts or pages.

_Developers! You can [extend](https://github.com/Automattic/remote-data-blocks/blob/trunk/docs/extending.md) the plugin by creating new datasources and queries._
