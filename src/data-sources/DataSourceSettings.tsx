import { __ } from '@wordpress/i18n';

import { AirtableSettings } from './airtable/AirtableSettings';
import { useDataSources } from './hooks/useDataSources';
import { ShopifySettings } from './shopify/ShopifySettings';
import { useSettingsContext } from '../settings/hooks/useSettingsNav';

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
		return <>{ __( 'Service not (yet) supported.', 'remote-data-blocks' ) }</>;
	}

	if ( ! uuid ) {
		return <>{ __( 'Data Source not found.', 'remote-data-blocks' ) }</>;
	}

	return <DataSourceEditSettings uuid={ uuid } />;
};
export default DataSourceSettings;
