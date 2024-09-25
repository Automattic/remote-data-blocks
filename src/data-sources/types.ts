import { SUPPORTED_SERVICES } from '@/data-sources/constants';
import { HttpAuth } from '@/data-sources/http/types';
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
	access_token: string;
	base: StringIdName;
	tables: StringIdName[];
}

export interface ShopifyConfig extends BaseDataSourceConfig {
	service: 'shopify';
	access_token: string;
	store_name: string;
}

export interface GoogleSheetsConfig extends BaseDataSourceConfig {
	service: 'google-sheets';
	credentials: GoogleServiceAccountKey;
	spreadsheet: StringIdName;
	sheet: NumberIdName;
}

export interface HttpConfig extends BaseDataSourceConfig {
	service: 'generic-http';
	url: string;
	auth: HttpAuth;
}

export type DataSourceConfig = AirtableConfig | ShopifyConfig | GoogleSheetsConfig | HttpConfig;

export type SettingsComponentProps< T extends BaseDataSourceConfig > = {
	mode: 'add' | 'edit';
	uuid?: string;
	config?: T;
};
