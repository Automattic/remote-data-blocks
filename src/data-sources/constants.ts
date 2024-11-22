import { __ } from '@wordpress/i18n';

import { SelectOption } from '@/types/input';

export const SUPPORTED_SERVICES = [
	'airtable',
	'shopify',
	'google-sheets',
	'generic-http',
	'example-api',
] as const;
export const SUPPORTED_SERVICES_LABELS: Record< ( typeof SUPPORTED_SERVICES )[ number ], string > =
	{
		airtable: __( 'Airtable', 'remote-data-blocks' ),
		shopify: __( 'Shopify', 'remote-data-blocks' ),
		'google-sheets': __( 'Google Sheets', 'remote-data-blocks' ),
		'generic-http': __( 'HTTP', 'remote-data-blocks' ),
		'example-api': __( 'Conference Events Example API', 'remote-data-blocks' ),
	} as const;
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
export const AUTH_TYPES = [ 'bearer', 'basic', 'api-key', 'none' ] as const;
export const API_KEY_ADD_TO = [ 'queryparams', 'header' ] as const;

/**
 * REST API Source SelectOptions
 */
export const HTTP_SOURCE_AUTH_TYPE_SELECT_OPTIONS: SelectOption<
	( typeof AUTH_TYPES )[ number ]
>[] = [
	{ label: __( 'Bearer', 'remote-data-blocks' ), value: 'bearer' },
	{ label: __( 'Basic', 'remote-data-blocks' ), value: 'basic' },
	{ label: __( 'API Key', 'remote-data-blocks' ), value: 'api-key' },
	{ label: __( 'None', 'remote-data-blocks' ), value: 'none' },
];
export const HTTP_SOURCE_ADD_TO_SELECT_OPTIONS: SelectOption<
	( typeof API_KEY_ADD_TO )[ number ]
>[] = [
	{ label: __( 'Header', 'remote-data-blocks' ), value: 'header' },
	{ label: __( 'Query Params', 'remote-data-blocks' ), value: 'queryparams' },
];
