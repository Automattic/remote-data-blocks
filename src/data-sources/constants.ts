import { SelectOption } from '@/types/input';

export const SUPPORTED_SERVICES = [
	'airtable',
	'shopify',
	'google-sheets',
	'http',
	'example-api',
] as const;
export const SUPPORTED_SERVICES_LABELS = new Map< ( typeof SUPPORTED_SERVICES )[ number ], string >(
	[
		[ 'airtable', 'Airtable' ],
		[ 'shopify', 'Shopify' ],
		[ 'google-sheets', 'Google Sheets' ],
		[ 'http', 'HTTP' ],
		[ 'example-api', 'Example API' ],
	]
);
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

/**
 * REST API Source Constants
 */
export const AUTH_TYPES = [ 'bearer', 'basic', 'api-key' ] as const;
export const API_KEY_ADD_TO = [ 'queryparams', 'header' ] as const;

/**
 * REST API Source SelectOptions
 */
export const HTTP_SOURCE_AUTH_TYPE_SELECT_OPTIONS: SelectOption<
	( typeof AUTH_TYPES )[ number ]
>[] = [
	{ label: 'Bearer', value: 'bearer' },
	{ label: 'Basic', value: 'basic' },
	{ label: 'API Key', value: 'api-key' },
];
export const HTTP_SOURCE_ADD_TO_SELECT_OPTIONS: SelectOption<
	( typeof API_KEY_ADD_TO )[ number ]
>[] = [
	{ label: 'Header', value: 'header' },
	{ label: 'Query Params', value: 'queryparams' },
];
