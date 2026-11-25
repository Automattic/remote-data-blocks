# Block bindings

Remote Data Blocks uses the [WordPress Block Bindings API](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-bindings/) to connect remote fields to block attributes. Select a block inside a Remote Data Block, then click an attribute row (marked with **+**) in its **Remote Data** inspector panel. Choose a field and click **Apply**. Click a bound field summary to change it, or use its **Remove** icon to disconnect it; field selectors appear only while editing a binding. **Cancel** or Escape discards an uncommitted selection. If no fields match an attribute, the chooser explains this instead of displaying an empty dropdown. Bindings are saved in the block's `metadata.bindings`, using the `remote-data/binding` source. Existing bindings keep the same saved format.

## Supported blocks and WordPress versions

The inspector follows the supported attributes exposed by the running WordPress editor. It does not maintain a second custom-block registration list. An attribute must also have a compatible scalar schema and a corresponding remote field to be useful.

- **WordPress 6.7 and 6.8:** a compatibility list preserves paragraph/heading `content`, image `id`, `url`, `title`, `alt`, and button `url`, `text`, `linkTarget`, `rel`. These versions do not expose the newer supported-attribute filters. Remote HTML remains an RDB-specific rendering path.
- **WordPress 6.9+:** WordPress exposes the supported-attribute map and the two filters shown below. RDB honors that map, including attributes removed by filters, rather than merging the old list back in. Dynamic block callbacks receive bound attributes; supported sourced attributes in static markup are updated by WordPress's HTML API. The source and selector restrictions below still apply.

New core attributes appear when the installed WordPress version supports them; installing newer RDB development dependencies does not add them to an older WordPress runtime. Dynamic blocks are not automatically bindable, and adding `__experimentalLabel` to a schema is not an opt-in mechanism.

## Custom blocks

Register the block and its attribute schema normally, then opt in through either official WordPress filter. This example uses both filters for a dynamic block:

```php
register_block_type( 'my-plugin/stock', [
    'api_version' => 3,
    'attributes' => [
        'description' => [ 'type' => 'string', 'default' => '' ],
        'quantity' => [ 'type' => 'number', 'default' => 0 ],
        'available' => [ 'type' => 'boolean', 'default' => false ],
    ],
    'render_callback' => static function ( array $attributes ): string {
        return sprintf(
            '<p>%s — %s remaining (%s)</p>',
            esc_html( $attributes['description'] ),
            esc_html( (string) $attributes['quantity'] ),
            $attributes['available'] ? 'available' : 'unavailable'
        );
    },
] );

// Generic filter: receives the block name as its second argument.
add_filter( 'block_bindings_supported_attributes', static function ( array $attributes, ?string $block_name ): array {
    if ( 'my-plugin/stock' === $block_name ) {
        $attributes[] = 'description';
    }
    return $attributes;
}, 10, 2 );

// Block-specific filter: receives only the attribute list.
add_filter( 'block_bindings_supported_attributes_my-plugin/stock', static function ( array $attributes ): array {
    return array_merge( $attributes, [ 'quantity', 'available' ] );
} );
```

The editor's matching JavaScript registration must declare the same attributes and display their values. On a runtime with generic custom rendering, place the block inside a Remote Data Block and bind the attributes through the inspector. No additional RDB filter is required.

Static blocks need a supported `source` and selector that match their saved HTML. Current generic rendering supports tag-name selectors (for example, `p`), not arbitrary CSS selectors such as `.my-class`. A schema declaration alone cannot tell WordPress how to rewrite arbitrary markup. Unsourced static values or unsupported source/selector combinations may need a render callback; test the actual saved content and frontend on the WordPress versions you support.

## Field compatibility

Field choices follow the target schema. Text attributes retain text formatting; numeric and boolean attributes retain their scalar types, including `0` and `false`. Invalid numeric conversions are not silently converted to zero. Number/integer fields are appropriate for attachment IDs; arbitrary string identifiers are not WordPress media IDs.

Text controls can show **Suggested fields** alongside other compatible fields. For example, the bundled Conference Event’s plain Title field can supply button text. **Show label** is available for paragraph and heading content.

New URL bindings require a field declared with a URL type. Use `button_url` for a Button’s URL and `image_url` for an Image’s URL. Other URL controls accept `url`, `button_url`, and `image_url`. For custom blocks, the chooser checks the HTML source attribute against WordPress Core’s default URI attribute list, which includes `href`, `src`, and `usemap`. When an attribute has no source declaration, a name such as `url` or `linkUrl` identifies it as a URL.

Saved plain-string URL bindings remain visible under **Current binding**, where you can inspect, replace, or remove them. To migrate, add a field with the appropriate URL type to the query output schema and map it to the same source path. Verify that the source returns the intended URLs before choosing the new field and clicking **Apply**. Adding a separate field preserves plain-text mappings that also use the original field.

WordPress Core escapes recognized URI attributes when rendering static blocks. Dynamic block render callbacks handle escaping for their own output.

Nested objects and arrays are not bindable through these scalar controls. HTML and Markdown belong in Remote HTML and its existing isolated rendering path, not in generic inline text fields.
