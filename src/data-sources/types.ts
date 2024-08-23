import { SUPPORTED_SERVICES } from './constants';

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
	base: string;
	table: string;
}

export interface ShopifyConfig extends BaseDataSourceConfig {
	service: 'shopify';
	store: string;
	token: string;
}

export type DataSourceConfig = AirtableConfig | ShopifyConfig;
