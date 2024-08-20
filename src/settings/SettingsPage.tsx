import { Panel } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

import Notices from './Notices';
import { SettingsContext, useDataSourceRouter } from './hooks/useSettingsNav';
import DataSourceList from '../data-sources/DataSourceList';
import DataSourceSettings from '../data-sources/DataSourceSettings';

function versionAndBuild() {
	const localized = window.REMOTE_DATA_BLOCKS_SETTINGS;
	const version = localized?.version ?? '0.0.0';
	const branch = localized?.branch ?? '';
	const hash = localized?.hash ?? '';

	const shortHash = hash.slice( 0, 7 );
	const devString = [ branch, shortHash ].filter( Boolean ).join( ' @ ' );

	return devString ? `${ version } (${ devString })` : version;
}

const SettingsPage = () => {
	const settingsContext = useDataSourceRouter();
	return (
		<SettingsContext.Provider value={ settingsContext }>
			<Notices />
			<Panel
				header={ sprintf(
					__( 'Remote Data Blocks Settings -- Version %s', 'remote-data-blocks' ),
					versionAndBuild()
				) }
			>
				{ [ 'addDataSource', 'editDataSource' ].includes( settingsContext.screen ) ? (
					<DataSourceSettings />
				) : (
					<DataSourceList />
				) }
			</Panel>
		</SettingsContext.Provider>
	);
};

export default SettingsPage;
