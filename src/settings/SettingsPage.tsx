import {
	Card,
	CardHeader,
	CardBody,
	MenuGroup,
	MenuItem,
	Dropdown,
	Button,
	Icon,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { chevronDown } from '@wordpress/icons';

import AirtableIcon from './icons/airtable';
import ShopifyIcon from './icons/shopify';
import DataSourceList from '@/data-sources/DataSourceList';
import DataSourceSettings from '@/data-sources/DataSourceSettings';
import { DataSourceType } from '@/data-sources/types';
import Notices from '@/settings/Notices';
import { SettingsContext, useDataSourceRouter } from '@/settings/hooks/useSettingsNav';

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

	const AddDataSourceDropdown = () => (
		<Dropdown
			className="add-data-source-dropdown"
			contentClassName="add-data-source-dropdown-content"
			focusOnMount={ false }
			popoverProps={ { placement: 'bottom-end' } }
			renderToggle={ ( { isOpen, onToggle } ) => (
				<Button
					className="add-data-source-btn"
					variant="primary"
					onClick={ onToggle }
					aria-expanded={ isOpen }
				>
					Add <Icon icon={ chevronDown } size={ 18 } />
				</Button>
			) }
			renderContent={ () => (
				<MenuGroup>
					<MenuItem icon={ AirtableIcon } iconPosition="left" onClick={ onAddDataSource }>
						Airtable
					</MenuItem>
					<MenuItem icon={ ShopifyIcon } iconPosition="left" onClick={ onAddDataSource }>
						Shopify
					</MenuItem>
				</MenuGroup>
			) }
		/>
	);

	function onAddDataSource( event: React.MouseEvent ) {
		const dataSource = event.currentTarget.textContent?.toLowerCase() as DataSourceType;
		const newUrl = new URL( window.location.href );

		newUrl.searchParams.set( 'addDataSource', dataSource );
		window.location.href = newUrl.href;
	}

	return (
		<>
			<div className="page-title">
				<h1>{ __( 'Remote Data Blocks', 'remote-data-blocks' ) }</h1>
				<p className="plugin-version">
					{ sprintf( __( '-- Version %s', 'remote-data-blocks' ), versionAndBuild() ) }
				</p>
			</div>
			<div className="page-content">
				<SettingsContext.Provider value={ settingsContext }>
					<Notices />

					<Card>
						<CardHeader>
							<h2>{ __( 'Data Sources', 'remote-data-blocks' ) }</h2>
							<AddDataSourceDropdown />
						</CardHeader>
						<CardBody>
							{ [ 'addDataSource', 'editDataSource' ].includes( settingsContext.screen ) ? (
								<DataSourceSettings />
							) : (
								<DataSourceList />
							) }
						</CardBody>
					</Card>
				</SettingsContext.Provider>
			</div>
		</>
	);
};

export default SettingsPage;
