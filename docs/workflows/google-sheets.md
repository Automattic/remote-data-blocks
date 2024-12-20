# Create a Google Sheets remote data block

This page will walk you through connecting a [Google Sheets](https://workspace.google.com/products/sheets/) data source. The remote block registration to display sheet records and the styling of that block is similar to the [Airtable workflow](./airtable-with-code.md). If you have not yet installed and activated the Remote Data Blocks plugin, visit [Getting Started](https://remotedatablocks.com/getting-started/).

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

## Block Registration and Styling

This would be similar to the [Airtable workflow](airtable.md). Refer the following sections from that workflow:

- [Create the data source](./airtable.md#create-the-data-source)
- [Insert the block](./airtable-with-code.md#insert-the-block)
- [Custom patterns and styling](./airtable-with-code.md#custom-patterns-and-styling)

## Code Reference

Check out [a working example](https://github.com/Automattic/remote-data-blocks/tree/trunk/example/google-sheets/westeros-houses) of the concepts above in the Remote Data Blocks GitHub repository and feel free to open an issue if you run into any difficulty when registering or customizing your remote data blocks.

### Westeros Houses Example Setup

Follow following setup steps to get the Westeros Houses example working:

- [Configure the Google Sheet API Access](./google-sheets-with-code.md#google-sheets-api-access) and [Create a new Google Sheet](./google-sheets-with-code.md#setting-up-the-google-sheet) by following the steps above.
- Add sheet named `Houses` inside the newly created Google Sheet with columns with headers as
  - House
  - Seat
  - Region
  - Words
  - Sigil (image url)
- Add some data to the sheet.
- Base64 encode the JSON key file and set it so that its available via `REMOTE_DATA_BLOCKS_EXAMPLE_GOOGLE_SHEETS_WESTEROS_HOUSES_ACCESS_TOKEN` constant.

Now the blocks with name `Westeros House` and `Westeros Houses List` should be available in the editor.
