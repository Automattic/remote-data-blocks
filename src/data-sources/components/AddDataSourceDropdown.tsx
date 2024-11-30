import { DropdownMenu, MenuGroup, MenuItem } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { chevronDown } from '@wordpress/icons';

import { SUPPORTED_SERVICES_LABELS } from '../constants';
import { useSettingsContext } from '@/settings/hooks/useSettingsNav';
import AirtableIcon from '@/settings/icons/AirtableIcon';
import GoogleSheetsIcon from '@/settings/icons/GoogleSheetsIcon';
import HttpIcon from '@/settings/icons/HttpIcon';
import ShopifyIcon from '@/settings/icons/ShopifyIcon';

export const AddDataSourceDropdown = () => {
	const { pushState } = useSettingsContext();

	function onAddDataSource( dataSource: string ) {
		const newUrl = new URL( window.location.href );
		newUrl.searchParams.set( 'addDataSource', dataSource );
		pushState( newUrl );
	}

	return (
		<DropdownMenu
			className="rdb-settings-page_add-data-source-dropdown"
			icon={ chevronDown }
			label={ __( 'Add source', 'remote-data-blocks' ) }
			text={ __( 'Add', 'remote-data-blocks' ) }
			toggleProps={ {
				className: 'rdb-settings-page_add-data-source-btn',
				variant: 'primary',
				showTooltip: false,
				style: { flexDirection: 'row-reverse', paddingLeft: '12px', paddingRight: '8px' },
			} }
			children={ ( { onClose } ) => (
				<MenuGroup>
					{ [
						{
							icon: AirtableIcon,
							label: SUPPORTED_SERVICES_LABELS.airtable,
							value: 'airtable',
						},
						{
							icon: GoogleSheetsIcon,
							label: SUPPORTED_SERVICES_LABELS[ 'google-sheets' ],
							value: 'google-sheets',
						},
						{
							icon: ShopifyIcon,
							label: SUPPORTED_SERVICES_LABELS.shopify,
							value: 'shopify',
						},
						{
							icon: HttpIcon,
							label: SUPPORTED_SERVICES_LABELS[ 'generic-http' ],
							value: 'generic-http',
						},
					].map( ( { icon, label, value } ) => (
						<MenuItem
							key={ value }
							icon={ icon }
							iconPosition="left"
							onClick={ () => {
								onAddDataSource( value );
								onClose();
							} }
						>
							{ label }
						</MenuItem>
					) ) }
				</MenuGroup>
			) }
		/>
	);
};
