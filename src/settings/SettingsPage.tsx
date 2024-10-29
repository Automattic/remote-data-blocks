import { __, sprintf } from '@wordpress/i18n';

import DataSourceList from '@/data-sources/DataSourceList';
import DataSourceSettings from '@/data-sources/DataSourceSettings';
import Notices from '@/settings/Notices';
import { SettingsContext, useDataSourceRouter } from '@/settings/hooks/useSettingsNav';

import './SettingsPage.scss';

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
		<div className="rdb-settings-page">
			<div className="page-title">
				<h1>{ __( 'Remote Data Blocks', 'remote-data-blocks' ) }</h1>
				<p className="plugin-version">
					{ sprintf( __( '-- Version %s', 'remote-data-blocks' ), versionAndBuild() ) }
				</p>
			</div>
			<div className="page-content">
				<SettingsContext.Provider value={ settingsContext }>
					<Notices />

					{ [ 'addDataSource', 'editDataSource' ].includes( settingsContext.screen ) ? (
						<DataSourceSettings />
					) : (
						<DataSourceList />
					) }
				</SettingsContext.Provider>
			</div>
		</div>
	);
};

export default SettingsPage;
