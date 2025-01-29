# Create a Salesforce Commerce B2C remote data block

This tutorial will walk you through connecting a [Salesforce Commerce B2C](https://developer.salesforce.com/docs/commerce/b2c-commerce/overview) data source and how to use the automatically created block in the WordPress editor.

## Salesforce Commerce B2C API Access

## Create the data source

1. Go to Settings > Remote Data Blocks in your WordPress admin.
2. Click on the "Connect new" button.
3. Choose "Salesforce Commerce B2C" from the dropdown menu as the data source type.
4. Name the data source. This name is only used for display purposes.
5. Provide the merchant short code. This is the region-specific merchant identifier.
6. Provide the organization ID.
7. Provide the client ID and the client secret. Ensure these are correct or else authentication will fail.

## Insert the block

Create or edit a page or post, then using the Block Inserter, search for the block using the name you provided in step four.

## Patterns and styling

You can use patterns to create a consistent, reusable layout for your remote data. You can read more about [patterns and other Core Concepts](../concepts/index.md#patterns).

Remote data blocks can be styled using the block editor's style settings, `theme.json`, or custom stylesheets. See the [example child theme](https://github.com/Automattic/remote-data-blocks/tree/trunk/example/theme) for more details.

## Code reference

You can also configure Salesforce Commerce D2C integrations with code. These integrations appear in the WordPress admin but can not be modified. You may wish to do this to have more control over the data source or because you have more advanced data processing needs.
