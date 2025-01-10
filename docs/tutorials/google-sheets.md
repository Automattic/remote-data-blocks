# Create a Google Sheets remote data block

This tutorial will walk you through connecting a [Google Sheets](https://workspace.google.com/products/sheets/) data source and how to use the automattically created block in the WordPress editor. 

## Google Sheets API Access

The Google Sheets API access is required for connecting to Google Sheets. The plugin uses a [service account](https://cloud.google.com/iam/docs/service-account-overview?hl=en) to authenticate requests to the Google Sheets API. The following steps are required to set up the Google Sheets API access:

- [Create a project](https://developers.google.com/workspace/guides/create-project) in Google Cloud Platform. `resourcemanager.projects.create` permission is needed to create a new project. Skip this step if you already have a project setup in your organization in Google Cloud Platform which can be used.
- Enable the Google [Sheets API](https://console.cloud.google.com/apis/library/sheets.googleapis.com) and [Drive API](https://console.cloud.google.com/apis/library/drive.googleapis.com)(required for listing spreadsheets) for your project.
- [Create a service account](https://cloud.google.com/iam/docs/service-accounts-create) which will be used to authenticate the requests to the Google Sheets API. Note down the service account email address.
- [Create a key](https://cloud.google.com/iam/docs/keys-create-delete) for the service account. This will download a JSON key file. Keep this file safe as it will be used to authenticate the block.
- Grant access of the service account email to the Google Sheet after which the service account can be used to authenticate the requests to the Google Sheets API for the given sheet.

The Service Account Keys JSON should be provided to your application securely. On WordPress VIP, we recommend using [environment variables](https://docs.wpvip.com/infrastructure/environments/manage-environment-variables/) to provide this token. The code in this example assumes that the Service Account Keys JSON has been Base64 encoded and provided securely via a constant.

## Setting up the Google Sheet

- Identify the Google Sheet that you want to connect to. If you have not created a Google Sheet yet, create one.
- Note down the Google Sheet ID from the URL. For example, in the URL `https://docs.google.com/spreadsheets/d/test_spreadsheet_id/edit?gid=0#gid=0`, the Google Sheet ID is `test_spreadsheet_id`. The Google Sheet ID is the unique identifier for the Google Sheet.
- Share the Google Sheet with the service account email address.

## Create the data source

1. Go to the Settings > Remote Data Blocks in your WordPress admin.
2. Click on the "Connect new" button.
3. Choose "Airtable" from the dropdown menu as the data source type.
4. Name this Data source, this name is only used internally. 
5. Enter the access token you created in Airtable.

If the personal access token is correct, you will be able to procede to the other steps. If you recieve an error check the token and try again.

6. Select your desired base, table, and fields.
7. Save the data source and return the data source list.

## Insert the block

Open a post for editing and select the block in the Block Inserter using the display name you provided.

<video src="https://github.com/user-attachments/assets/67f22710-b1bd-4f2c-a410-2e20fe27b348"></video>

## Patterns and styling

You can use patterns to create conistent reuasble layout for your remote data. You can read more about [patterns and other Core Concepts](../concepts/index.md#patterns).

Remote data blocks can be styled using the block editor's style settings, `theme.json`, or custom stylesheets. See the [example child theme](https://github.com/Automattic/remote-data-blocks/tree/trunk/example/theme) for more details.

## Code Reference

Check out [a working example](https://github.com/Automattic/remote-data-blocks/tree/trunk/example/google-sheets/westeros-houses) of the concepts above in the Remote Data Blocks GitHub repository.


