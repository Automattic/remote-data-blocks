import { SUPPORTED_SERVICES, AUTH_TYPES, API_KEY_ADD_TO } from '@/data-sources/constants';
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

export interface BaseHttpAuth {
	type: ( typeof AUTH_TYPES )[ number ];
	value: string;
}

export interface HttpBearerAuth extends BaseHttpAuth {
	type: 'bearer';
}

export interface HttpBasicAuth extends BaseHttpAuth {
	type: 'basic';
}

export type HttpAuth = HttpBearerAuth | HttpBasicAuth | HttpApiKeyAuth;

export interface HttpApiKeyAuth extends BaseHttpAuth {
	type: 'api-key';
	key: string;
	addTo: ( typeof API_KEY_ADD_TO )[ number ];
}

export type HttpAuthFormState = {
	authType: ( typeof AUTH_TYPES )[ number ];
	authValue: string;
	authKey: string;
	authAddTo: ( typeof API_KEY_ADD_TO )[ number ];
};

export interface HttpConfig extends BaseDataSourceConfig {
	service: 'http';
	url: string;
	auth: HttpAuth;
}

export type DataSourceConfig = AirtableConfig | ShopifyConfig | GoogleSheetsConfig | HttpConfig;

export type SettingsComponentProps< T extends BaseDataSourceConfig > = {
	mode: 'add' | 'edit';
	uuid?: string;
	config?: T;
};
