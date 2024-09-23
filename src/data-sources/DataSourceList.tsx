import {
	Button,
	ButtonGroup,
	Card,
	CardBody,
	CardHeader,
	Dropdown,
	Icon,
	MenuGroup,
	MenuItem,
	Modal,
	Placeholder,
	Spinner,
	__experimentalText as Text,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { chevronDown, edit, info, trash } from '@wordpress/icons';

import { useDataSources } from '@/data-sources/hooks/useDataSources';
import { DataSourceConfig } from '@/data-sources/types';
import { useSettingsContext } from '@/settings/hooks/useSettingsNav';
import AirtableIcon from '@/settings/icons/airtable';
import GoogleSheetsIcon from '@/settings/icons/google-sheets';
import ShopifyIcon from '@/settings/icons/shopify';
import { slugToTitleCase } from '@/utils/string';

import './data-source-list.scss';

const DataSourceList = () => {
	const { dataSources, loadingDataSources, deleteDataSource, fetchDataSources } = useDataSources();
	const [ dataSourceToDelete, setDataSourceToDelete ] = useState< DataSourceConfig | null >( null );
	const { pushState } = useSettingsContext();

	const onCancelDeleteDialog = () => {
		setDataSourceToDelete( null );
	};

	const onDeleteDataSource = ( source: DataSourceConfig ) => setDataSourceToDelete( source );

	const onEditDataSource = ( uuidToEdit: string ) => {
		const newUrl = new URL( window.location.href );
		newUrl.searchParams.set( 'editDataSource', uuidToEdit );
		pushState( newUrl );
	};

	const onConfirmDeleteDataSource = async ( source: DataSourceConfig ) => {
		await deleteDataSource( source ).catch( () => null );
		setDataSourceToDelete( null );
		await fetchDataSources().catch( () => null );
	};

	const renderDataSourceMeta = ( source: DataSourceConfig ) => {
		const tags = [];
		switch ( source.service ) {
			case 'airtable':
				tags.push( source.base.name, source.table.name );
				break;
			case 'shopify':
				tags.push( source.store );
				break;
			case 'google-sheets':
				tags.push( source.spreadsheet.name, source.sheet.name );
				break;
		}

		return tags.map( tag => (
			<span key={ tag } className="data-source-meta">
				{ tag }
			</span>
		) );
	};

	const AddDataSourceDropdown = () => {
		function onAddDataSource( dataSource: string ) {
			const newUrl = new URL( window.location.href );
			newUrl.searchParams.set( 'addDataSource', dataSource );
			pushState( newUrl );
		}

		return (
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
						{ [
							{
								icon: AirtableIcon,
								label: __( 'Airtable', 'remote-data-blocks' ),
								value: 'airtable',
							},
							{
								icon: GoogleSheetsIcon,
								label: __( 'Google Sheets', 'remote-data-blocks' ),
								value: 'google-sheets',
							},
							{ icon: ShopifyIcon, label: __( 'Shopify', 'remote-data-blocks' ), value: 'shopify' },
						].map( ( { icon, label, value } ) => (
							<MenuItem
								key={ value }
								icon={ icon }
								iconPosition="left"
								onClick={ () => onAddDataSource( value ) }
							>
								{ label }
							</MenuItem>
						) ) }
					</MenuGroup>
				) }
			/>
		);
	};

	const CardBodyContent = (): JSX.Element => {
		if ( loadingDataSources ) {
			return (
				<div className="card-loader">
					<Spinner />
					<p> { __( 'Loading data sources...', 'remote-data-blocks' ) } </p>
				</div>
			);
		}

		if ( dataSources.length === 0 ) {
			return (
				<Placeholder
					icon={ info }
					label={ __( 'No data source found.', 'remote-data-blocks' ) }
					instructions={ __( 'Use “Add” button to add data source.', 'remote-data-blocks' ) }
				/>
			);
		}

		return (
			<div className="data-source-list-wrapper">
				<table className="table data-source-list">
					<thead className="table-header">
						<tr>
							<th>{ __( 'Slug', 'remote-data-blocks' ) }</th>
							<th>{ __( 'Data Source', 'remote-data-blocks' ) }</th>
							<th>{ __( 'Meta', 'remote-data-blocks' ) }</th>
							<th className="data-source-actions">{ __( 'Actions', 'remote-data-blocks' ) }</th>
						</tr>
					</thead>
					<tbody className="table-body">
						{ dataSources
							.sort( ( a, b ) => a.slug.localeCompare( b.slug ) )
							.map( source => {
								const { uuid, slug, service } = source;
								return (
									<tr key={ slug } className="table-row">
										<td>
											<Text className="data-source-slug">{ slug }</Text>
										</td>
										<td>
											<Text>{ slugToTitleCase( service ) }</Text>
										</td>
										<td> { renderDataSourceMeta( source ) } </td>
										<td className="data-source-actions">
											{ uuid && (
												<ButtonGroup className="data-source-actions">
													<Button variant="secondary" onClick={ () => onEditDataSource( uuid ) }>
														<Icon icon={ edit } />
													</Button>
													<Button
														variant="secondary"
														onClick={ () => onDeleteDataSource( source ) }
													>
														<Icon icon={ trash } />
													</Button>
												</ButtonGroup>
											) }
										</td>
									</tr>
								);
							} ) }
					</tbody>
				</table>

				{ dataSourceToDelete && (
					<Modal
						className="confirm-delete-data-source-modal"
						title={ __( 'Delete Data Source', 'remote-data-blocks' ) }
						size="medium"
						onRequestClose={ () => {
							onCancelDeleteDialog();
						} }
						isDismissible={ true }
						focusOnMount
						shouldCloseOnEsc={ true }
						shouldCloseOnClickOutside={ true }
					>
						<p>
							{ sprintf(
								__(
									'Are you sure you want to delete "%s" data source with slug "%s"?',
									'remote-data-blocks'
								),
								slugToTitleCase( dataSourceToDelete.service ),
								dataSourceToDelete.slug
							) }
						</p>

						<div className="action-buttons">
							<Button variant="link" onClick={ onCancelDeleteDialog }>
								{ __( 'Cancel', 'remote-data-blocks' ) }
							</Button>
							<Button
								variant="primary"
								isDestructive
								onClick={ () => void onConfirmDeleteDataSource( dataSourceToDelete ) }
							>
								{ __( 'Confirm', 'remote-data-blocks' ) }
							</Button>
						</div>
					</Modal>
				) }
			</div>
		);
	};

	return (
		<Card className="data-source-list-card">
			<CardHeader>
				<h2>{ __( 'Data Sources', 'remote-data-blocks' ) }</h2>
				<AddDataSourceDropdown />
			</CardHeader>
			<CardBody>
				<CardBodyContent />
			</CardBody>
		</Card>
	);
};

export default DataSourceList;
