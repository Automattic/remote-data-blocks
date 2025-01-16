# Example: "Westeros Houses" blocks

This example registers remote data blocks for a Google Sheet that contains information about [the noble houses of Westeros](https://awoiaf.westeros.org/index.php/Houses_of_Westeros).

Like the [Airtable Events example](../../airtable/events/README.md), you can register these blocks [without writing any code](https://remotedatablocks.com/gh/docs/workflows/google-sheets/). However, if you'd like to replicate this code-based example, you'll need to set up a Google Sheet with the required data.

1. [Configure the Google Sheet API Access](../../../docs/tutorials/google-sheets.md).
2. Base64 encode the JSON key file and make it available via the `EXAMPLE_GOOGLE_SHEETS_WESTEROS_HOUSES_ENCODED_CREDENTIALS` constant.
3. Add a sheet named `Houses` with the follong columns headers in the first row:

- House
- Seat
- Region
- Words
- Sigil

4. Add some data to the sheet.
