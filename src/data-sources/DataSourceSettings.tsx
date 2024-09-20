import { __ } from '@wordpress/i18n';

import { AirtableSettings } from '@/data-sources/airtable/AirtableSettings';
import { GoogleSheetsSettings } from '@/data-sources/google-sheets/GoogleSheetsSettings';
import { GraphQLSettings } from '@/data-sources/graphql/GraphQLSettings';
import { useDataSources } from '@/data-sources/hooks/useDataSources';
import { RestApiSettings } from '@/data-sources/rest-api/RestApiSettings';
import { ShopifySettings } from '@/data-sources/shopify/ShopifySettings';
import { useSettingsContext } from '@/settings/hooks/useSettingsNav';

import './data-source-settings.scss';

interface DataSourceEditSettings {
	uuid: string;
}

const DataSourceEditSettings = ( { uuid }: DataSourceEditSettings ) => {
	const { dataSources, loadingDataSources } = useDataSources();
	if ( loadingDataSources ) {
		return <>{ __( 'Loading data source...', 'remote-data-blocks' ) }</>;
	}
	const dataSource = dataSources.find( source => source.uuid === uuid );
	if ( ! dataSource ) {
		return <>{ __( 'Data Source not found.', 'remote-data-blocks' ) }</>;
	}
	if ( 'airtable' === dataSource.service ) {
		return <AirtableSettings mode="edit" uuid={ uuid } config={ dataSource } />;
	}
	if ( 'shopify' === dataSource.service ) {
		return <ShopifySettings mode="edit" uuid={ uuid } config={ dataSource } />;
	}

	if ( 'google-sheets' === dataSource.service ) {
		return <GoogleSheetsSettings mode="edit" uuid={ uuid } config={ dataSource } />;
	}
	if ( 'rest-api' === dataSource.service ) {
		return <RestApiSettings mode="edit" uuid={ uuid } config={ dataSource } />;
	}
	if ( 'graphql' === dataSource.service ) {
		return <GraphQLSettings mode="edit" uuid={ uuid } config={ dataSource } />;
	}

	return <>{ __( 'Service not (yet) supported.', 'remote-data-blocks' ) }</>;
};

const DataSourceSettings = () => {
	const { screen, service, uuid } = useSettingsContext();
	const mode = screen === 'addDataSource' ? 'add' : 'edit';

	if ( 'add' === mode ) {
		if ( 'airtable' === service ) {
			return <AirtableSettings mode="add" />;
		}
		if ( 'shopify' === service ) {
			return <ShopifySettings mode="add" />;
		}
		if ( 'google-sheets' === service ) {
			return <GoogleSheetsSettings mode="add" />;
		}
		if ( 'rest-api' === service ) {
			return <RestApiSettings mode="add" />;
		}
		if ( 'graphql' === service ) {
			return <GraphQLSettings mode="add" />;
		}
		return <>{ __( 'Service not (yet) supported.', 'remote-data-blocks' ) }</>;
	}

	if ( ! uuid ) {
		return <>{ __( 'Data Source not found.', 'remote-data-blocks' ) }</>;
	}

	return <DataSourceEditSettings uuid={ uuid } />;
};
export default DataSourceSettings;
