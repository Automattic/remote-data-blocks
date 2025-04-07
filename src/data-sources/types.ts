import { ConfigSource, SUPPORTED_SERVICES } from '@/data-sources/constants';
import { HttpAuth } from '@/data-sources/http/types';
import { StringIdName } from '@/types/common';
import { GoogleServiceAccountKey } from '@/types/google';

export type DataSourceType = ( typeof SUPPORTED_SERVICES )[ number ];

interface BaseServiceConfig extends Record< string, unknown > {
	__version: number;
	display_name: string;
	enable_blocks: boolean;
}
interface BaseDataSourceConfig<
	ServiceName extends DataSourceType,
	ServiceConfig extends BaseServiceConfig
> {
	errors?: WP_Error[];
	service: ServiceName;
	service_config: ServiceConfig;
	uuid: string | null;
	config_source: ConfigSource;
}

interface WP_Error {
	code: string;
	message: string;
}

export interface DataSourceQueryMappingValue {
	key: string;
	name?: string;
	path?: string;
	type?: string;
	prefix?: string;
}

export interface AirtableTableConfig extends StringIdName {
	output_query_mappings: DataSourceQueryMappingValue[];
}

export interface AirtableServiceConfig extends BaseServiceConfig {
	access_token: string;
	base: StringIdName;
	tables: AirtableTableConfig[];
}

export interface GoogleSheetsSheetConfig extends StringIdName {
	output_query_mappings: DataSourceQueryMappingValue[];
}

export interface GoogleSheetsServiceConfig extends BaseServiceConfig {
	credentials: GoogleServiceAccountKey;
	spreadsheet: StringIdName;
	sheets: GoogleSheetsSheetConfig[];
}

export interface HttpServiceConfig extends BaseServiceConfig {
	auth?: HttpAuth;
	endpoint: string;
}

export interface ShopifyServiceConfig extends BaseServiceConfig {
	access_token: string;
	store_name: string;
}

export type AirtableConfig = BaseDataSourceConfig< 'airtable', AirtableServiceConfig >;
export type GoogleSheetsConfig = BaseDataSourceConfig< 'google-sheets', GoogleSheetsServiceConfig >;
export type HttpConfig = BaseDataSourceConfig< 'generic-http', HttpServiceConfig >;
export type ShopifyConfig = BaseDataSourceConfig< 'shopify', ShopifyServiceConfig >;

export type DataSourceConfig = AirtableConfig | GoogleSheetsConfig | HttpConfig | ShopifyConfig;

export type SettingsComponentProps< T extends DataSourceConfig > = {
	mode: 'add' | 'edit';
	uuid?: string;
	config?: T;
};
