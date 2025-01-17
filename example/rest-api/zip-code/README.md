# Example: "Zip Code" block

This example plugin registers a remote data block to represent the city and state from a US ZIP code. The block fetches data from the [Zippopotam.us API](http://www.zippopotam.us/).

When working with REST APIs that do not have a first-class integration (like Airtable, Google Sheets, Shopify, et al.), a common approach is to define a data source on the settings screen and then commit a custom query in code to fetch and process the data.

This example illustrates this approach, and assumes you have configured the data source in the UI and have provided the UUID via the `EXAMPLE_ZIP_CODE_DATA_SOURCE_UUID` constant.

To enable it, simply copy the zipcode.php file to your plugins folder, replace the UUID, and activate it. This will expose a new custom block called `Zip Code'
