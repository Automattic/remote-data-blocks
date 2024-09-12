export const SUPPORTED_SERVICES = [ 'airtable', 'shopify', 'google-sheets' ] as const;
export const OPTIONS_PAGE_SLUG = 'remote-data-blocks-settings';
export const REST_BASE = '/remote-data-blocks/v1';
export const REST_BASE_DATA_SOURCES = `${ REST_BASE }/data-sources`;
export const REST_BASE_AUTH = `${ REST_BASE }/auth`;
/**
 * Google API scopes for Google Sheets and Google Drive (to list spreadsheets)
 */
export const GOOGLE_SHEETS_API_SCOPES = [
	'https://www.googleapis.com/auth/drive.readonly',
	'https://www.googleapis.com/auth/spreadsheets.readonly',
];
