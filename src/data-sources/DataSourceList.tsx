import {
	__experimentalConfirmDialog as ConfirmDialog,
	__experimentalText as Text,
	Button,
	ButtonGroup,
	Spinner,
	Placeholder,
} from '@wordpress/components';
import { DialogInputEvent } from '@wordpress/components/src/confirm-dialog/types';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { info } from '@wordpress/icons';

import { Tag } from '@/components/tag';
import { useDataSources } from '@/data-sources/hooks/useDataSources';
import { DataSourceConfig } from '@/data-sources/types';
import { useSettingsContext } from '@/settings/hooks/useSettingsNav';
import { toTitleCase } from '@/utils/string';

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
			<div className="data-sources-loader">
				<Spinner />
				<p> { __( 'Loading data sources...', 'remote-data-blocks' ) } </p>
			</div>
		);
	}

	if ( dataSources.length === 0 ) {
		return (
			<Placeholder
				className="data-sources-placeholder"
				icon={ info }
				label={ __( 'No data source found.', 'remote-data-blocks' ) }
				instructions={ __( 'Use “Add” button to add data source.', 'remote-data-blocks' ) }
			/>
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
	);
};

export default DataSourceList;
