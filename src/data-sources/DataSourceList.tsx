import {
	__experimentalConfirmDialog as ConfirmDialog,
	__experimentalHeading as Heading,
	__experimentalText as Text,
	Button,
	PanelBody,
	PanelRow,
	ButtonGroup,
	Spinner,
} from '@wordpress/components';
import { DialogInputEvent } from '@wordpress/components/src/confirm-dialog/types';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import AddDataSourceModal from './AddDataSourceModal';
import { useDataSources } from './hooks/useDataSources';
import { DataSourceConfig, DataSourceType } from './types';
import { Tag } from '../components/tag';
import { useSettingsContext } from '../settings/hooks/useSettingsNav';
import { toTitleCase } from '../utils/string';

const DataSourceList = () => {
	const { dataSources, loadingDataSources, deleteDataSource, fetchDataSources } = useDataSources();
	const [ deleteDialogOpen, setDeleteDialogOpen ] = useState( false );
	const { pushState } = useSettingsContext();

	const onCancelDeleteDialog = ( event: DialogInputEvent ) => {
		if ( event?.type === 'click' ) {
			setDeleteDialogOpen( false );
		}
	};

	const openDeleteDialog = () => setDeleteDialogOpen( true );

	const onServiceTypeSelected = ( serviceToAdd: DataSourceType ) => {
		const newUrl = new URL( window.location.href );
		newUrl.searchParams.set( 'addDataSource', serviceToAdd );
		pushState( newUrl );
	};

	const onEditClick = ( uuidToEdit: string ) => {
		const newUrl = new URL( window.location.href );
		newUrl.searchParams.set( 'editDataSource', uuidToEdit );
		pushState( newUrl );
	};

	const onDeleteConfirm = async ( uuid: string ) => {
		await deleteDataSource( uuid ).catch( () => null );
		setDeleteDialogOpen( false );
		await fetchDataSources().catch( () => null );
	};

	if ( loadingDataSources ) {
		return (
			<>
				{ __( 'Loading data sources...', 'remote-data-blocks' ) }
				<Spinner />
			</>
		);
	}

	const getValidDataSources = () => {
		return dataSources.filter( source => [ 'airtable', 'shopify' ].includes( source.service ) );
	};

	const renderDataSourceMeta = ( source: DataSourceConfig ) => {
		if ( source.service === 'airtable' ) {
			return (
				<>
					<Tag id="airtable-base" label="Base" value={ source.base.name } />
					<Tag id="airtable-table" label="Table" value={ source.table.name } />
				</>
			);
		}

		if ( source.service === 'shopify' ) {
			return (
				<>
					<Tag id="shopify-store" label="Store" value={ source.store } />
				</>
			);
		}

		return null;
	};

	return (
		<PanelBody title={ __( 'Configure Data Sources', 'remote-data-blocks' ) }>
			<PanelRow>
				<AddDataSourceModal onSubmit={ onServiceTypeSelected } />
			</PanelRow>
			<hr />
			<PanelRow>
				<Heading className="data-source-list-heading" level={ 3 }>
					{ __( 'Available Data Sources', 'remote-data-blocks' ) }
				</Heading>
			</PanelRow>
			<PanelRow>
				<table className="table data-source-list">
					<thead className="table-header">
						<tr>
							<th style={ { textAlign: 'left' } }>{ __( 'Slug', 'remote-data-blocks' ) }</th>
							<th style={ { textAlign: 'left' } }>{ __( 'Service', 'remote-data-blocks' ) }</th>
							<th style={ { textAlign: 'left' } }>{ __( 'Meta', 'remote-data-blocks' ) }</th>
							<th style={ { textAlign: 'left' } }>{ __( 'Actions', 'remote-data-blocks' ) }</th>
						</tr>
					</thead>
					<tbody className="table-body">
						{ getValidDataSources().map( source => {
							const { uuid, slug, service } = source;
							return (
								<tr key={ uuid } className="table-row">
									<td>
										<Text>{ slug }</Text>
									</td>
									<td>
										<Text>{ toTitleCase( service ) }</Text>
									</td>
									<td>
										<div className="data-source-meta">{ renderDataSourceMeta( source ) }</div>
									</td>
									<td>
										<ConfirmDialog
											isOpen={ deleteDialogOpen }
											onCancel={ onCancelDeleteDialog }
											onConfirm={ () => void onDeleteConfirm( uuid ) }
										>
											{ __( 'Are you sure you want to delete?' ) }
										</ConfirmDialog>

										<ButtonGroup>
											<Button variant="tertiary" onClick={ openDeleteDialog }>
												{ __( 'Delete', 'remote-data-blocks' ) }
											</Button>
											<Button variant="primary" onClick={ () => onEditClick( uuid ) }>
												{ __( 'Edit', 'remote-data-blocks' ) }
											</Button>
										</ButtonGroup>
									</td>
								</tr>
							);
						} ) }
					</tbody>
				</table>
			</PanelRow>
		</PanelBody>
	);
};

export default DataSourceList;
