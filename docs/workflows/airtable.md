# Create an Airtable remote data block

This page will walk you through connecting an [Airtable](https://airtable.com/) data source, registering a remote data block to display table records, and customizing the styling of that block. It will require you to commit code to a WordPress theme or plugin. If you have not yet installed and activated the Remote Data Blocks plugin, visit [Getting Started](https://remotedatablocks.com/getting-started/).

## Base and personal access token

First, identify an Airtable base and table that you would like to use as a data source. This example uses a base created from the default [“Event planning” template](https://www.airtable.com/templates/event-planning/exppdJtYjEgfmd6Sq), accessible from the Airtable home screen after logging in. We will target the “Schedule” table from that base.

<p><img width="375" alt="airtable-template" src="https://github.com/user-attachments/assets/a5be04c6-d72c-4cf2-9e62-814af54f9a35"></p>

Next, [create a personal access token](https://airtable.com/create/tokens) that has the `data.records:read` and `schema.bases:read` scopes and has access to the base or bases you wish to use.

<p><img width="939" alt="create-pat" src="https://github.com/user-attachments/assets/16b43ea3-ebf9-4904-8c65-a3040de902d4"></p>

This personal access token is a secret that should be provided to your application securely. On WordPress VIP, we recommend using [environment variables](https://docs.wpvip.com/infrastructure/environments/manage-environment-variables/) to provide this token. The code in this example assumes that the token has been provided securely via a constant named `AIRTABLE_EVENTS_ACCESS_TOKEN`.

## Create the data source

1. Go to the Settings > Remote Data Blocks in your WordPress admin.
2. Click on the "Connect new" button.
3. Choose "Airtable" from the dropdown menu as the data source type.
4. Fill in the form to select your desired base, table, and fields.
5. Save the data source and return the data source list.

## Insert the block

Open a post for editing and select the block in the Block Inserter using the display name you provided.

https://github.com/user-attachments/assets/67f22710-b1bd-4f2c-a410-2e20fe27b348

## Custom patterns and styling

You can improve upon the default appearance of the block by creating your own patterns. Patterns can be associated with a remote data block in the "Pattern" settings in the sidebar of the pattern editor. Once associated with a remote data block, patterns will appear in the pattern selection modal. The Remote Data Blocks plugin supports both synced and unsynced patterns.

https://github.com/user-attachments/assets/358d9d40-557b-4f39-b943-ed73d6f18adb

Alternatively, you can alter the style of a remote data block using `theme.json` and / or custom stylesheets. See the [example child theme](https://github.com/Automattic/remote-data-blocks/tree/trunk/example/theme) in the Remote Data Blocks GitHub repository for more details.

## Code reference

Check out [a working example](https://github.com/Automattic/remote-data-blocks/tree/trunk/example/airtable/events) of the concepts above in the Remote Data Blocks GitHub repository and feel free to open an issue if you run into any difficulty when registering or customizing your remote data blocks.
