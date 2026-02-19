# Block bindings

Remote Data Blocks takes advantage of the [block bindings API](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-bindings/). This core WordPress API allows you to “bind” dynamic data to the attributes of core blocks, which are then reflected in the final HTML markup. Generally, this avoids the need to write and maintain custom blocks.

For a quick overview of block bindings, the [announcement post](https://make.wordpress.org/core/2024/03/06/new-feature-the-block-bindings-api/) is very helpful; for a deeper dive, consult the [public documentation](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-bindings/). That said, an in-depth understanding of block bindings isn't necessary to use Remote Data Blocks: just know that the plugin is built on core, stable WordPress APIs.

## Supported Core Blocks

Remote Data Blocks supports block bindings for the following WordPress core blocks:

### Static Blocks
- **Paragraph** (`core/paragraph`) - `content` attribute
- **Heading** (`core/heading`) - `content` attribute
- **Image** (`core/image`) - `url`, `alt`, `title` attributes
- **Button** (`core/button`) - `url`, `text`, `linkTarget`, `rel` attributes
- **Details** (`core/details`) - `content` attribute (experimental)

### Dynamic Blocks
- **Post Title** (`core/post-title`) - `content` attribute
- **Post Date** (`core/post-date`) - `content` attribute
- **Post Excerpt** (`core/post-excerpt`) - `content` attribute
- **Post Featured Image** (`core/post-featured-image`) - `url`, `alt` attributes
- **Post Author** (`core/post-author`) - `content` attribute

## Extending Supported Blocks

You can extend the list of supported blocks using WordPress filters:

### JavaScript Filter

```javascript
import { addFilter } from '@wordpress/hooks';

addFilter(
'remote_data_blocks_supported_blocks',
'my-plugin/add-custom-blocks',
( supportedBlocks, blockName, settings ) => {
// Add your custom block to the list
return [ ...supportedBlocks, 'my-plugin/custom-block' ];
}
);
```

### Automatic Detection

Blocks that define bindable attributes using WordPress core's `__experimentalLabel` property or `supports.bindings` configuration will be automatically supported without needing to add them to the filter.
