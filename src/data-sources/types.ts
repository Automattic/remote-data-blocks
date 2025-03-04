import { SUPPORTED_SERVICES, ConfigSource } from '@/data-sources/constants';
import { HttpAuth } from '@/data-sources/http/types';
import { StringIdName } from '@/types/common';
import { GoogleServiceAccountKey } from '@/types/google';
import { SelectOption } from '@/types/input';

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
	service: ServiceName;
	service_config: ServiceConfig;
	uuid: string | null;
	config_source: ConfigSource;
}

// Base Query Config
interface BaseQueryConfig {
	uuid: string | null;
	config_source: ConfigSource;
	data_source_uuid: string;
}

interface BaseQueryServiceConfig extends Record< string, unknown > {
	query_type: string;
	enable_blocks: boolean;
}

interface BaseQueryDataSourceConfig<
	ServiceName extends DataSourceType,
	QueryConfig extends BaseQueryServiceConfig
> extends BaseQueryConfig {
	service: ServiceName;
	query_config: QueryConfig;
}

export interface DataSourceQueryMappingValue {
	key: string;
	name?: string;
	path?: string;
	type?: string;
	prefix?: string;
}

// DataSource Config Types
export interface AirtableTableConfig extends StringIdName {
	output_query_mappings: DataSourceQueryMappingValue[];
}

export interface AirtableServiceConfig extends BaseServiceConfig {
	access_token: string;
}

export interface GoogleSheetsSheetConfig extends StringIdName {
	output_query_mappings: DataSourceQueryMappingValue[];
}

export interface GoogleSheetsServiceConfig extends BaseServiceConfig {
	credentials: GoogleServiceAccountKey;
}

export interface HttpServiceConfig extends BaseServiceConfig {
	auth?: HttpAuth;
	endpoint: string;
}

export interface SalesforceD2CStoreConfig extends StringIdName {
	output_query_mappings: DataSourceQueryMappingValue[];
}

export interface SalesforceD2CServiceConfig extends BaseServiceConfig {
	client_id: string;
	client_secret: string;
	domain: string;
}

export interface SalesforceD2CWebStoreRecord {
	/** The name of the WebStore */
	name: string;
	/** The unique identifier for the WebStore */
	id: string;
}

export interface SalesforceD2CWebStoresResponse {
	webstores: SalesforceD2CWebStoreRecord[];
}

export interface ShopifyServiceConfig extends BaseServiceConfig {
	access_token: string;
	store_name: string;
}

// Query Config Types
export interface AirtableQueryConfig extends BaseQueryServiceConfig {
	base: StringIdName;
	tables: AirtableTableConfig[];
}

export interface GoogleSheetsQueryConfig extends BaseQueryServiceConfig {
	spreadsheet: StringIdName;
	sheets: GoogleSheetsSheetConfig[];
}

export interface HttpQueryConfig extends BaseQueryServiceConfig {
}

export interface SalesforceD2CQueryConfig extends BaseQueryServiceConfig {
	store_id: string;
}

export interface ShopifyQueryConfig extends BaseQueryServiceConfig {
}

// DataSource Types
export type AirtableConfig = BaseDataSourceConfig< 'airtable', AirtableServiceConfig >;
export type GoogleSheetsConfig = BaseDataSourceConfig< 'google-sheets', GoogleSheetsServiceConfig >;
export type HttpConfig = BaseDataSourceConfig< 'generic-http', HttpServiceConfig >;
export type SalesforceD2CConfig = BaseDataSourceConfig<
	'salesforce-d2c',
	SalesforceD2CServiceConfig
>;
export type ShopifyConfig = BaseDataSourceConfig< 'shopify', ShopifyServiceConfig >;

export type DataSourceConfig =
	| AirtableConfig
	| GoogleSheetsConfig
	| HttpConfig
	| SalesforceD2CConfig
	| ShopifyConfig;

// Query Types
export type AirtableQueryDataSourceConfig = BaseQueryDataSourceConfig< 'airtable', AirtableQueryConfig >;
export type GoogleSheetsQueryDataSourceConfig = BaseQueryDataSourceConfig< 'google-sheets', GoogleSheetsQueryConfig >;
export type HttpQueryDataSourceConfig = BaseQueryDataSourceConfig< 'generic-http', HttpQueryConfig >;
export type SalesforceD2CQueryDataSourceConfig = BaseQueryDataSourceConfig<
	'salesforce-d2c',
	SalesforceD2CQueryConfig
>;
export type ShopifyQueryDataSourceConfig = BaseQueryDataSourceConfig< 'shopify', ShopifyQueryConfig >;

export type QueryDataSourceConfig =
	| AirtableQueryDataSourceConfig
	| GoogleSheetsQueryDataSourceConfig
	| HttpQueryDataSourceConfig
	| SalesforceD2CQueryDataSourceConfig
	| ShopifyQueryDataSourceConfig;

// Component Props
export type SettingsComponentProps< T extends DataSourceConfig > = {
	mode: 'add' | 'edit';
	uuid?: string;
	config?: T;
	onSuccess?: (dataSource: DataSourceConfig) => void;
};

export type QuerySettingsComponentProps< T extends QueryDataSourceConfig > = {
	mode: 'add' | 'edit';
	uuid?: string;
	config?: T;
	dataSourceUuid?: string;
};

export interface DataSourceFormModalProps {
	isOpen: boolean;
	onRequestClose: () => void;
	onDataSourceCreated?: (dataSource: DataSourceConfig) => void;
}
