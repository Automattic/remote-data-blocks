import {
	__experimentalConfirmDialog as ConfirmDialog,
	__experimentalHeading as Heading,
	__experimentalText as Text,
	Button,
	PanelBody,
	PanelRow,
	ButtonGroup,
	Spinner,
	Flex,
} from '@wordpress/components';
import { DialogInputEvent } from '@wordpress/components/src/confirm-dialog/types';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import AddDataSourceModal from './AddDataSourceModal';
import { useDataSources } from './hooks/useDataSources';
import { DataSourceType } from './types';
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
				<ul className="data-source-list">
					{ dataSources.map( source => {
						if ( [ 'airtable', 'shopify' ].includes( source.service ) ) {
							const { uuid } = source;
							return (
								<li key={ uuid } className="data-source-list-item">
									<Flex>
										<Text>{ source.slug }</Text>
									</Flex>
									<Text>{ toTitleCase( source.service ) }</Text>
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
								</li>
							);
						}
						return null;
					} ) }
				</ul>
			</PanelRow>
		</PanelBody>
	);
};

export default DataSourceList;
