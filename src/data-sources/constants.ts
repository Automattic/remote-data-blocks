import { __ } from '@wordpress/i18n';

import { HttpApiKeyDestination, HttpAuthTypes } from '@/data-sources/http/types';
import { SelectOption } from '@/types/input';

export const SUPPORTED_SERVICES = [
	'airtable',
	'example-api',
	'generic-http',
	'google-sheets',
	'shopify',
] as const;
export const SUPPORTED_SERVICES_LABELS: Record< ( typeof SUPPORTED_SERVICES )[ number ], string > =
	{
		airtable: __( 'Airtable', 'remote-data-blocks' ),
		'example-api': __( 'Conference Events Example API', 'remote-data-blocks' ),
		'generic-http': __( 'HTTP', 'remote-data-blocks' ),
		'google-sheets': __( 'Google Sheets', 'remote-data-blocks' ),
		shopify: __( 'Shopify', 'remote-data-blocks' ),
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
 * REST API Source SelectOptions
 */
export const HTTP_SOURCE_AUTH_TYPE_SELECT_OPTIONS: SelectOption< HttpAuthTypes >[] = [
	{ label: __( 'None', 'remote-data-blocks' ), value: 'none' },
	{ label: __( 'Basic', 'remote-data-blocks' ), value: 'basic' },
	{ label: __( 'Bearer', 'remote-data-blocks' ), value: 'bearer' },
	{ label: __( 'Custom', 'remote-data-blocks' ), value: 'api-key' },
];
export const HTTP_SOURCE_ADD_TO_SELECT_OPTIONS: SelectOption< HttpApiKeyDestination >[] = [
	{ label: __( 'HTTP header', 'remote-data-blocks' ), value: 'header' },
	{ label: __( 'Query parameter', 'remote-data-blocks' ), value: 'queryparams' },
];

/**
 * Data source component labels
 */
export const HTTP_SOURCE_AUTH_VALUE_LABELS: Record< HttpAuthTypes, string > = {
	'api-key': __( 'Value', 'remote-data-blocks' ),
	basic: __( 'Credentials', 'remote-data-blocks' ),
	bearer: __( 'Bearer token', 'remote-data-blocks' ),
	none: __( 'Ignored', 'remote-data-blocks' ),
};

/**
 * Data source component help text
 */
export const HTTP_SOURCE_AUTH_TYPE_HELP_TEXT: Record< HttpAuthTypes, string > = {
	basic: __( 'The credentials will be sent in an Authorization header.', 'remote-data-blocks' ),
	'api-key': __( 'Construct a custom HTTP header or query parameter.', 'remote-data-blocks' ),
	bearer: __( 'The bearer token will be sent in an Authorization header.', 'remote-data-blocks' ),
	none: __( 'No authentication required.', 'remote-data-blocks' ),
};
export const HTTP_SOURCE_AUTH_VALUE_HELP_TEXT: Record< HttpAuthTypes, string > = {
	'api-key': __( 'The value of the HTTP header or query parameter.', 'remote-data-blocks' ),
	basic: __(
		'The credentials are usually a string in the format “username:password”. The string will be base64-encoded and prepended with “Basic” by this plugin.',
		'remote-data-blocks'
	),
	bearer: __( 'The token will be prepended with “Bearer” by this plugin.', 'remote-data-blocks' ),
	none: __( 'Ignored', 'remote-data-blocks' ),
};

export enum ConfigSource {
	CODE = 'code',
	STORAGE = 'storage',
	CONSTANTS = 'constant',
}
