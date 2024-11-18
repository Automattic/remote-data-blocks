import { Button, ExternalLink, __experimentalHStack as HStack } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { chevronLeft } from '@wordpress/icons';

import DataSourceList from '@/data-sources/DataSourceList';
import DataSourceSettings from '@/data-sources/DataSourceSettings';
import { AddDataSourceDropdown } from '@/data-sources/components/AddDataSourceDropdown';
import Notices from '@/settings/Notices';
import { SettingsContext, useDataSourceRouter } from '@/settings/hooks/useSettingsNav';

import './SettingsPage.scss';

const SettingsPage = () => {
	const settingsContext = useDataSourceRouter();

	return (
		<div className="rdb-settings-page">
			<SettingsContext.Provider value={ settingsContext }>
				<div className="rdb-settings-page_header">
					{ [ 'addDataSource', 'editDataSource' ].includes( settingsContext.screen ) ? (
						<HStack className="rdb-settings-page_header-return">
							<Button icon={ chevronLeft } onClick={ () => settingsContext.goToMainScreen() } />
							<h2>
								{ __(
									`${
										[ 'addDataSource' ].includes( settingsContext.screen ) ? 'New ' : 'Edit'
									} Data Source`,
									'remote-data-blocks'
								) }
							</h2>
						</HStack>
					) : (
						<>
							<h1>{ __( 'Data', 'remote-data-blocks' ) }</h1>
							<p>
								{ __(
									'Add and manage data sources used for blocks and content across your site. '
								) }
								<ExternalLink href="https://remotedatablocks.com/">
									{ __( 'Learn more', 'remote-data-blocks' ) }
								</ExternalLink>
							</p>
							<AddDataSourceDropdown />
						</>
					) }
				</div>
				<div className="page-content">
					<Notices />

					{ [ 'addDataSource', 'editDataSource' ].includes( settingsContext.screen ) ? (
						<DataSourceSettings />
					) : (
						<DataSourceList />
					) }
				</div>
			</SettingsContext.Provider>
		</div>
	);
};

export default SettingsPage;
