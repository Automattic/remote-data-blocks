# Example: "Leaflet Map" block

This example illustrates the flexibility of the Remote Data Blocks plugin. Instead of registering a block via `register_remote_data_block`, this example builds a custom dynamic block that uses the [Leaflet library](https://leafletjs.com) to display a map with marked locations.

The map locations are loaded from an Airtable base that contains longitude and latitude coordinates. Instead of using block bindings, this example creates a data source and a query and executes it manually in `render.php`.

The result is a registered "Leaflet Map" block that renders remote data in the block editor and on the WordPress frontend.

<p><img width="700" alt="A Leaflet Map block in the block editor" src="https://github.com/user-attachments/assets/25f23e1a-2088-4b7e-896a-781c656294a5" /></p>

<p><img width="700" alt="A Leaflet Map block in the WordPress frontend" src="https://github.com/user-attachments/assets/979e60ae-c5f4-47f4-8cd8-69c5d3fa2c52" /></p>

## Build step

Because the custom block uses JSX, it requires a build step, which is provided by this repository. You can rebuild the example after changes by running `npm run build:examples`.

If you copy this example code to your own repository, we recommend using [the `@wordpress/create-block` utility](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-create-block/) to scaffold your custom block and configure the build step.
