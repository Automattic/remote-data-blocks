# Create a Shopify remote data block

This tutorial will walk you through connecting a [Shopify](https://www.shopify.com/) data source and how to use the automattically created block in the WordPress editor. 

## Shopify API Access

## Create the data source

1. Go to the Settings > Remote Data Blocks in your WordPress admin.
2. Click on the "Connect new" button.
3. Choose "Shopify" from the dropdown menu as the data source type.
4. Name this Data source, this name is only used internally. 
5. Enter the sub-domain of your Shopify store. If you aren't sure about this, log into Shopipy, the sub-domain of your store is the portion of the URL before myshopify.com.

If the credentials are correct, you will be able to save the sdata source. If you recieve an error check the token and try again.

## Insert the block

Open a post for editing and select the block in the Block Inserter using the display name you provided. You can select which product you would like to display in the post.

![How inserting a Shopify block looks in the WordPress Editor](insert-shopify-block.gif)

## Patterns and styling

You can use patterns to create conistent reuasble layout for your remote data. You can read more about [patterns and other Core Concepts](../concepts/index.md#patterns).

Remote data blocks can be styled using the block editor's style settings, `theme.json`, or custom stylesheets. See the [example child theme](https://github.com/Automattic/remote-data-blocks/tree/trunk/example/theme) for more details.

## Code Reference

Check out [a working example](https://github.com/Automattic/remote-data-blocks/tree/trunk/example/shopify) of the concepts above in the Remote Data Blocks GitHub repository.
