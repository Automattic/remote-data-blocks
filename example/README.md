# Example code and templates

The example code and templates in this directory can help you get started with the Remote Data Blocks plugin. Note that many tasks can be performed in the UI without writing any code. However, other tasks require custom code, especially when you want to work with generic REST APIs or customize the block output or behavior.

## Block examples

These blocks communicate with APIs that do not require authentication. Uncomment lines at the end of `remote-data-blocks.php` to enable them. They are roughly in order of complexity, starting with the simplest.

- [Zip Code block](./blocks/zip-code-block/zip-code-block.php)
- [Art block](./blocks/art-block/art-block.php)
- [Shopify Mock Store block](./blocks/shopify-mock-store-block/shopify-mock-store-block.php)
- [GitHub Markdown File block](./blocks/github-markdown-file/github-markdown-file.php)

## Templates

These code templates require credentials and other customization to work. They are a useful starting point for exploration and are especially useful as context for AI agents.

- Airtable
  - ["Event Planning" Airtable blocks](./airtable/events/README.md)
  - ["Leaflet Map" Airtable block](./airtable/leaflet-map/README.md)
- Google Sheets
  - ["Westeros Houses" blocks](./google-sheets/westeros-houses/README.md)
- GitHub
  - ["GitHub Markdown File" block](./github/markdown-file/README.md)
- Shopify
  - ["Shopify Product" block](./shopify/product/README.md)
- REST API
  - ["Art Institute of Chicago" block](./rest-api/art-institute/README.md)
  - ["Zip Code" block](./rest-api/zip-code/README.md)
- [Theme example](./theme/README.md)
