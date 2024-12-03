import {
	Button,
	ButtonGroup,
	__experimentalConfirmDialog as ConfirmDialog,
	Icon,
	Placeholder,
	Spinner,
	__experimentalText as Text,
} from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { copy, edit, info, trash } from '@wordpress/icons';
import { store as noticesStore, NoticeStoreActions, WPNotice } from '@wordpress/notices';

import { SUPPORTED_SERVICES, SUPPORTED_SERVICES_LABELS } from './constants';
import { useDataSources } from '@/data-sources/hooks/useDataSources';
import { DataSourceConfig } from '@/data-sources/types';
import { useSettingsContext } from '@/settings/hooks/useSettingsNav';

import './DataSourceList.scss';

const DataSourceList = () => {
	const { createSuccessNotice, createErrorNotice } =
		useDispatch< NoticeStoreActions >( noticesStore );
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
				tags.push( source.base.name ?? source.base.id );
				break;
			case 'shopify':
				tags.push( source.store_name );
				break;
			case 'google-sheets':
				tags.push( source.spreadsheet.name );
				break;
		}

		return tags.filter( Boolean ).map( tag => (
			<span key={ tag } className="data-source-meta">
				{ tag }
			</span>
		) );
	};

	const getServiceLabel = ( service: ( typeof SUPPORTED_SERVICES )[ number ] ) => {
		// eslint-disable-next-line security/detect-object-injection
		return SUPPORTED_SERVICES_LABELS[ service ];
	};

	function showSnackbar( type: 'success' | 'error', message: string ): void {
		const SNACKBAR_OPTIONS: Partial< WPNotice > = {
			isDismissible: true,
		};

		switch ( type ) {
			case 'success':
				createSuccessNotice( message, { ...SNACKBAR_OPTIONS, icon: '✅' } );
				break;
			case 'error':
				createErrorNotice( message, { ...SNACKBAR_OPTIONS, icon: '❌' } );
				break;
		}
	}

	const DataSourceTable = (): JSX.Element => {
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
					instructions={ __(
						'Use the “Connect New” button to add a data source.',
						'remote-data-blocks'
					) }
				/>
			);
		}

		return (
			<div className="data-source-list-wrapper">
				<table className="table data-source-list">
					<thead className="table-header">
						<tr>
							<th>{ __( 'Name', 'remote-data-blocks' ) }</th>
							<th>{ __( 'Data Source', 'remote-data-blocks' ) }</th>
							<th>{ __( 'Meta', 'remote-data-blocks' ) }</th>
							<th className="data-source-actions">{ __( 'Actions', 'remote-data-blocks' ) }</th>
						</tr>
					</thead>
					<tbody className="table-body">
						{ dataSources
							.sort( ( a, b ) => ( a.display_name ?? '' ).localeCompare( b.display_name ?? '' ) )
							.map( source => {
								const { display_name: displayName, uuid, service } = source;

								return (
									<tr key={ uuid } className="table-row">
										<td>
											<Text className="data-source-display_name">{ displayName }</Text>
										</td>
										<td>
											<Text>{ getServiceLabel( service ) }</Text>
										</td>
										<td> { renderDataSourceMeta( source ) } </td>
										<td className="data-source-actions">
											{ uuid && SUPPORTED_SERVICES.includes( service ) && (
												<ButtonGroup className="data-source-actions">
													<Button
														variant="secondary"
														onClick={ () => {
															if ( uuid ) {
																navigator.clipboard
																	.writeText( uuid )
																	.then( () => {
																		showSnackbar(
																			'success',
																			__(
																				'Copied data source UUID to the clipboard.',
																				'remote-data-blocks'
																			)
																		);
																	} )
																	.catch( () =>
																		showSnackbar(
																			'error',
																			__( 'Failed to copy to clipboard.', 'remote-data-blocks' )
																		)
																	);
															}
														} }
													>
														<Icon icon={ copy } />
													</Button>
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
					<ConfirmDialog
						confirmButtonText={ __( 'Confirm', 'remote-data-blocks' ) }
						onCancel={ () => onCancelDeleteDialog() }
						onConfirm={ () => void onConfirmDeleteDataSource( dataSourceToDelete ) }
						size="medium"
						title={ __( 'Delete Data Source', 'remote-data-blocks' ) }
					>
						{ sprintf(
							__( 'Are you sure you want to delete %s data source "%s"?', 'remote-data-blocks' ),
							getServiceLabel( dataSourceToDelete.service ),
							dataSourceToDelete.display_name
						) }
					</ConfirmDialog>
				) }
			</div>
		);
	};

	return <DataSourceTable />;
};

export default DataSourceList;
