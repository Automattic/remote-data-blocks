# Block registration

Use the `register_remote_data_block` function to register your block and associate it with your query and data source.

```php
function register_your_custom_block() {
    $block_name       = 'Your Custom Block';
    $your_data_source = new YourCustomDataSource();
    $your_query       = new YourCustomQuery( $your_data_source );

    register_remote_data_block( $block_name, $your_query );
}
add_action( 'init', 'YourNamespace\\register_your_custom_block', 10, 0 );
```
