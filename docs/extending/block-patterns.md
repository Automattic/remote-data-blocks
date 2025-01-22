# Block Patterns

Patterns allow you to represent your remote data in different ways. The plugin registers an unstyled block pattern anytime you register a remote data block either in the WordPress admin or with `register_remote_data_block`. You can create additional patterns in the WordPress Site Editor or programmatically by passing a `patterns` property to your block options.

You cannot edit the default pattern, but you can duplicate it and make changes. We recommend starting with a duplicate and then making changes in the Site Editor. If you want to lock the pattern down from further edits, copy the block markup from the editor and associate the pattern via code.

## Example

```html
<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
    <!-- wp:heading {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"field":"title"}}}}} -->
    <h2 class="wp-block-heading"></h2>
    <!-- /wp:heading -->
    <!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"remote-data/binding","args":{"field":"description"}}}}} -->
    <p></p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
