# Example code and templates

The example code and templates in this directory can help you get started with the Remote Data Blocks plugin. Note that many tasks can be performed in the UI without writing any code. However, other tasks require custom code, especially when you want to work with generic REST APIs or customize the block output or behavior.

## Block examples

These blocks communicate with APIs that do not require authentication. Uncomment lines at the end of `remote-data-blocks.php` to enable them. They are roughly in order of complexity, starting with the simplest.

- [Zip Code block](./blocks/zip-code-block/zip-code-block.php)
- [Art block](./blocks/art-block/art-block.php)
- [Shopify Mock Store block](./blocks/shopify-mock-store-block/shopify-mock-store-block.php)
- [Book block](./blocks/book-block/book-block.php)
- [Weather block](./blocks/weather-block/weather-block.php)
- [GitHub Markdown File block](./blocks/github-markdown-block/github-markdown-block.php)

## Templates

These code templates require credentials and other customization to work. They are a useful starting point for exploration and are especially useful as context for AI agents.

- [REST API block](templates/rest-api-block)
- [REST API block from UI-created data source](templates/rest-api-block-from-ui-data-source)
- [Airtable block](templates/airtable-block)
- [Airtable map block](templates/airtable-map-block)
- [Google Sheets block](templates/google-sheets-block)
- [Shopify Product block](templates/shopify-product-block)
- [Example child theme](templates/theme)
