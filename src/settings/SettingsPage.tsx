import {
	Button,
	ExternalLink,
	TabPanel,
	__experimentalHStack as HStack
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { chevronLeft } from '@wordpress/icons';

import DataSourceList from '@/data-sources/DataSourceList';
import DataSourceSettings from '@/data-sources/DataSourceSettings';
import QueryList from '@/data-sources/QueryList';
import { AddDataSourceDropdown } from '@/data-sources/components/AddDataSourceDropdown';
import Notices from '@/settings/Notices';
import { SettingsContext, useDataSourceRouter } from '@/settings/hooks/useSettingsNav';

const SettingsPage = () => {
	const settingsContext = useDataSourceRouter();
	const [ activeTab, setActiveTab ] = useState<string>( () => {
		// Initialize from URL if possible
		const urlParams = new URLSearchParams( window.location.search );
		return urlParams.get( 'tab' ) === 'queries' ? 'queries' : 'data-sources';
	} );

	const addOrEditScreen = [ 'addDataSource', 'editDataSource', 'addQuery', 'editQuery' ].includes( settingsContext.screen );

	const tabs = [
		{
			name: 'data-sources',
			title: __( 'Data Sources', 'remote-data-blocks' ),
			className: 'data-sources-tab',
		},
		{
			name: 'queries',
			title: __( 'Queries', 'remote-data-blocks' ),
			className: 'queries-tab',
		},
	];

	const handleCreateQuery = () => {
		const newUrl = new URL( window.location.href );
		newUrl.searchParams.set( 'addQuery', 'true' );
		settingsContext.pushState( newUrl );
	};

	return (
		<div className="rdb-settings-page">
			<Notices />

			<SettingsContext.Provider value={ settingsContext }>
				<div className="rdb-settings-page_header">
					{ addOrEditScreen ? (
						<HStack justify="flex-start">
							<Button icon={ chevronLeft } onClick={ () => settingsContext.goToMainScreen() } />
							<HStack>
								<h2>
									{ __(
										`${
											settingsContext.screen === 'addDataSource' ? 'New Data Source' : 
											settingsContext.screen === 'editDataSource' ? 'Edit Data Source' :
											settingsContext.screen === 'addQuery' ? 'New Query' : 'Edit Query'
										}`,
										'remote-data-blocks'
									) }
								</h2>
								<HStack expanded={ false } justify="flex-end" spacing={ 3 }>
									<div id="rdb-settings-page-form-save-button" />
									<div id="rdb-settings-page-form-settings" />
								</HStack>
							</HStack>
						</HStack>
					) : (
						<>
							<h1>{ __( 'Remote Data Blocks', 'remote-data-blocks' ) }</h1>
							<p>
								{ __(
									'Add and manage data sources and queries used for blocks and content across your site. '
								) }
								<ExternalLink href="https://remotedatablocks.com/">
									{ __( 'Learn more', 'remote-data-blocks' ) }
								</ExternalLink>
							</p>
							<div className="rdb-settings-page_actions">
								{ activeTab === 'data-sources' ? 
									<AddDataSourceDropdown /> : 
									<Button variant="primary" onClick={ handleCreateQuery }>
										{ __( 'Add New Query', 'remote-data-blocks' ) }
									</Button>
								}
							</div>
						</>
					) }
				</div>

				{ addOrEditScreen ? (
					<div className="rdb-settings-page_content rdb-settings-page_add-edit">
						<DataSourceSettings />
					</div>
				) : (
					<TabPanel
						className="rdb-settings-page_tabs"
						activeClass="is-active"
						tabs={ tabs }
						onSelect={ ( tabName: string ) => {
							setActiveTab( tabName );
							// Update URL with tab
							const newUrl = new URL( window.location.href );
							newUrl.searchParams.set( 'tab', tabName );
							settingsContext.pushState( newUrl );
						} }
					>
						{ ( tab ) => {
							// The key prop helps React maintain component state between tab switches
							return (
								<div
									key={tab.name}
									className={ `rdb-settings-page_content rdb-settings-page_${tab.name}` }
								>
									{ tab.name === 'data-sources' ? 
										<DataSourceList /> : 
										<QueryList />
									}
								</div>
							);
						} }
					</TabPanel>
				) }
			</SettingsContext.Provider>
		</div>
	);
};

export default SettingsPage;