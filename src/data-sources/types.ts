import { SUPPORTED_SERVICES } from '@/data-sources/constants';
import { HttpAuth } from '@/data-sources/http/types';
import { NumberIdName, StringIdName } from '@/types/common';
import { GoogleServiceAccountKey } from '@/types/google';

export type DataSourceType = ( typeof SUPPORTED_SERVICES )[ number ];

interface BaseServiceConfig extends Record< string, unknown > {
	__version: number;
	display_name: string;
}

interface BaseDataSourceConfig<
	ServiceName extends DataSourceType,
	ServiceConfig extends BaseServiceConfig
> {
	service: ServiceName;
	service_config: ServiceConfig;
	uuid: string | null;
}

export interface DataSourceQueryMappingValue {
	key: string;
	name: string;
	path: string;
	type: string;
}

export type DataSourceQueryMapping = Record< string, DataSourceQueryMappingValue >;

export interface DataSourceQuery {
	isCollection: boolean;
	mappings?: DataSourceQueryMapping;
}

/**
 * Currently this is an subset of DataSourceQueryMappingValue. Following fields are inferred:
 * - `path` can be constructed automatically assuming we use same field names we get from Airtable.
 * - `type` is always string for now.
 */
export interface AirtableOutputQueryMappingValue {
	key: string;
	name?: string;
	path?: string;
	type?: string;
	prefix?: string;
}

export interface AirtableTableConfig extends StringIdName {
	output_query_mappings: AirtableOutputQueryMappingValue[];
}

export interface AirtableServiceConfig extends BaseServiceConfig {
	access_token: string;
	base: StringIdName;
	tables: AirtableTableConfig[];
}

export interface GoogleSheetsServiceConfig extends BaseServiceConfig {
	credentials: GoogleServiceAccountKey;
	spreadsheet: StringIdName;
	sheet: NumberIdName;
}

export interface HttpServiceConfig extends BaseServiceConfig {
	auth?: HttpAuth;
	endpoint: string;
}

export interface SalesforceB2CServiceConfig extends BaseServiceConfig {
	shortcode: string;
	organization_id: string;
	client_id: string;
	client_secret: string;
}

export interface ShopifyServiceConfig extends BaseServiceConfig {
	access_token: string;
	store_name: string;
}

export type AirtableConfig = BaseDataSourceConfig< 'airtable', AirtableServiceConfig >;
export type GoogleSheetsConfig = BaseDataSourceConfig< 'google-sheets', GoogleSheetsServiceConfig >;
export type HttpConfig = BaseDataSourceConfig< 'generic-http', HttpServiceConfig >;
export type SalesforceB2CConfig = BaseDataSourceConfig<
	'salesforce-b2c',
	SalesforceB2CServiceConfig
>;
export type ShopifyConfig = BaseDataSourceConfig< 'shopify', ShopifyServiceConfig >;

export type DataSourceConfig =
	| AirtableConfig
	| GoogleSheetsConfig
	| HttpConfig
	| SalesforceB2CConfig
	| ShopifyConfig;

export type SettingsComponentProps< T extends DataSourceConfig > = {
	mode: 'add' | 'edit';
	uuid?: string;
	config?: T;
};
