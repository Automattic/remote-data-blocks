import {
	SUPPORTED_SERVICES,
	AUTH_TYPES,
	API_KEY_ADD_TO,
	HTTP_METHODS,
} from '@/data-sources/constants';
import { NumberIdName, StringIdName } from '@/types/common';
import { GoogleServiceAccountKey } from '@/types/google';

export type DataSourceType = ( typeof SUPPORTED_SERVICES )[ number ];

interface BaseDataSourceConfig {
	uuid: string;
	service: DataSourceType;
	slug: string;
}

export interface DataSourceQueryMappingValue {
	name: string;
	path: string;
	type: 'id' | 'string';
}

export type DataSourceQueryMapping = Record< string, DataSourceQueryMappingValue >;

export interface DataSourceQuery {
	isCollection: boolean;
	mappings?: DataSourceQueryMapping;
}

export interface AirtableConfig extends BaseDataSourceConfig {
	service: 'airtable';
	token: string;
	base: StringIdName;
	table: StringIdName;
}

export interface ShopifyConfig extends BaseDataSourceConfig {
	service: 'shopify';
	store: string;
	token: string;
}

export interface GoogleSheetsConfig extends BaseDataSourceConfig {
	service: 'google-sheets';
	credentials: GoogleServiceAccountKey;
	spreadsheet: StringIdName;
	sheet: NumberIdName;
}

export interface BaseRestApiAuth {
	type: ( typeof AUTH_TYPES )[ number ];
	value: string;
}

export interface RestApiBearerAuth extends BaseRestApiAuth {
	type: 'bearer';
}

export interface RestApiBasicAuth extends BaseRestApiAuth {
	type: 'basic';
}

export type ApiAuth = RestApiBearerAuth | RestApiBasicAuth | RestApiApiKeyAuth;

export interface RestApiApiKeyAuth extends BaseRestApiAuth {
	type: 'api-key';
	key: string;
	addTo: ( typeof API_KEY_ADD_TO )[ number ];
}

export interface RestApiConfig extends BaseDataSourceConfig {
	service: 'rest-api';
	url: string;
	method: ( typeof HTTP_METHODS )[ number ];
	auth: ApiAuth;
}

export type ApiAuthFormState = {
	authType: ( typeof AUTH_TYPES )[ number ];
	authValue: string;
	authKey: string;
	authAddTo: ( typeof API_KEY_ADD_TO )[ number ];
};

export interface GraphQLConfig extends Omit< RestApiConfig, 'service' > {
	service: 'graphql';
	query: string;
}

export type DataSourceConfig =
	| AirtableConfig
	| ShopifyConfig
	| GoogleSheetsConfig
	| RestApiConfig
	| GraphQLConfig;
