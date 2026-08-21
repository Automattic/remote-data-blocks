# Block bindings

Remote Data Blocks takes advantage of the [block bindings API](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-bindings/). This core WordPress API allows you to "bind" dynamic data to the attributes of core blocks, which are then reflected in the final HTML markup. Generally, this avoids the need to write and maintain custom blocks.

For a quick overview of block bindings, the [announcement post](https://make.wordpress.org/core/2024/03/06/new-feature-the-block-bindings-api/) is very helpful; for a deeper dive, consult the [public documentation](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-bindings/). That said, an in-depth understanding of block bindings isn't necessary to use Remote Data Blocks: just know that the plugin is built on core, stable WordPress APIs.

## Automatic Support for Block Bindings

Remote Data Blocks automatically supports all blocks that WordPress core designates as supporting block bindings. This includes:

- Core blocks like **Paragraph**, **Heading**, **Image**, **Button**, and **Details**
- Dynamic blocks like **Post Title**, **Post Date**, **Post Excerpt**, **Post Featured Image**, and **Post Author**
- Any custom blocks that register bindable attributes through WordPress core's mechanisms

The plugin queries WordPress's `__experimentalBlockBindingsSupportedAttributes` setting to determine which blocks and attributes support bindings, ensuring compatibility with current and future WordPress versions without requiring manual updates.

## Extending Block Bindings

To add block binding support to your custom blocks, follow [WordPress's official documentation](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-bindings/#extending-supported-attributes) on extending supported attributes. Once registered with WordPress core, your blocks will automatically work with Remote Data Blocks.

Example of making a custom block attribute bindable:

```javascript
registerBlockType( 'my-plugin/custom-block', {
attributes: {
customField: {
type: 'string',
__experimentalLabel: 'Custom Field', // Makes this attribute bindable
},
},
} );
```
