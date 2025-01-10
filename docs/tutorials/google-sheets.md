# Create a Google Sheets remote data block

This tutorial will walk you through connecting a [Google Sheets](https://workspace.google.com/products/sheets/) data source and how to use the automattically created block in the WordPress editor. 

## Google Sheets API Access

The Google Sheets API access is required for connecting to Google Sheets. The plugin uses a [service account](https://cloud.google.com/iam/docs/service-account-overview?hl=en) to authenticate requests to the Google Sheets API. The following steps are required to set up the Google Sheets API access:

- [Create a project](https://developers.google.com/workspace/guides/create-project) in Google Cloud Platform. `resourcemanager.projects.create` permission is needed to create a new project. Skip this step if you already have a project setup in your organization in Google Cloud Platform which can be used.
- Enable the Google [Sheets API](https://console.cloud.google.com/apis/library/sheets.googleapis.com) and [Drive API](https://console.cloud.google.com/apis/library/drive.googleapis.com)(required for listing spreadsheets) for your project. You can access these from the links above or by clicking on "Enabled APIs & services" in the left hand menu and then the "+ ENABLE APIS AND SERVICES" in the top center of the screen.
- [Create a service account](https://cloud.google.com/iam/docs/service-accounts-create) which will be used to authenticate the requests to the Google Sheets API. You will need to Enable the IAM API first, and then if you scroll down further on the page linked above, you can click the button to "Go to Create service account." 
- Select the "Owner" role and note down the service account email address.
- You will need to create the JSON key for this account. You can access the key by clicking on the three dots under Actions in the Service account table and choosing "Manage Keys." 
- Click to "Add Key" and choose the JSON type. The file will be automatically downloaded. Keep this file safe as it will be used to authenticate the block.
- Grant access of the service account email to the Google Sheet after which the service account can be used to authenticate the requests to the Google Sheets API for the given sheet.

## Setting up the Google Sheet

- Identify the Google Sheet that you want to connect to. 
- Share the Google Sheet with the service account email address you noted above. Viewer access is suffecient.
- Note down the Google Sheet ID from the URL. For example, in the URL `https://docs.google.com/spreadsheets/d/test_spreadsheet_id/edit?gid=0#gid=0`, the Google Sheet ID is `test_spreadsheet_id`. The Google Sheet ID is the unique identifier for the Google Sheet.

## Create the data source

1. Go to the Settings > Remote Data Blocks in your WordPress admin.
2. Click on the "Connect new" button.
3. Choose "Google Sheets" from the dropdown menu as the data source type.
4. Name this Data source, this name is only used internally. 
5. Enter the contents of the JSON file you downloaded.

If the credentials are correct, you will be able to procede to the other steps. If you recieve an error check the token and try again.

6. Select your desired spreadsheet, sheet, and fields.
7. Save the data source and return the data source list.

## Insert the block

Open a post for editing and select the block in the Block Inserter using the display name you provided. You will notice there is both a loop and a single block available.

The loop block will return all the entries in the spreadsheet.

## Patterns and styling

You can use patterns to create conistent reuasble layout for your remote data. You can read more about [patterns and other Core Concepts](../concepts/index.md#patterns).

Remote data blocks can be styled using the block editor's style settings, `theme.json`, or custom stylesheets. See the [example child theme](https://github.com/Automattic/remote-data-blocks/tree/trunk/example/theme) for more details.

## Code Reference

Check out [a working example](https://github.com/Automattic/remote-data-blocks/tree/trunk/example/google-sheets/westeros-houses) of the concepts above in the Remote Data Blocks GitHub repository.


