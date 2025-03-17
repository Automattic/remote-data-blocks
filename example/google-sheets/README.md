# Westeros Houses Example Setup

Follow following setup steps to get the Westeros Houses example working:

- [Configure the Google Sheet API Access](../../docs/tutorials/google-sheets.md#google-sheets-api-access) and [Create a new Google Sheet](../../docs/tutorials/google-sheets.md##setting-up-the-google-sheet) by following the steps in the tutorial.
- Add sheet named `Houses` inside the newly created Google Sheet with the follong columns headers in the first row.
  - House
  - Seat
  - Region
  - Words
  - Sigil
- Add some data to the sheet.
- Base64 encode the JSON key file and set it so that its available via `REMOTE_DATA_BLOCKS_EXAMPLE_GOOGLE_SHEETS_WESTEROS_HOUSES_ACCESS_TOKEN` constant.

Now the blocks with name `Westeros House` and `Westeros Houses List` should be available in the editor.
