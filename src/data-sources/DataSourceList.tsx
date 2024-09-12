import {
	__experimentalText as Text,
	Button,
	ButtonGroup,
	Spinner,
	Placeholder,
	Icon,
	Modal,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { edit, info, trash } from '@wordpress/icons';

import { useDataSources } from '@/data-sources/hooks/useDataSources';
import { DataSourceConfig } from '@/data-sources/types';
import { useSettingsContext } from '@/settings/hooks/useSettingsNav';
import { toTitleCase } from '@/utils/string';

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
		const tags = [];
		switch ( source.service ) {
			case 'airtable':
				tags.push( source.base.name, source.table.name );
				break;
			case 'shopify':
				tags.push( source.store );
				break;
		}

		return tags.map( ( tag, index ) => (
			<span key={ index } className="data-source-meta">
				{ tag }
			</span>
		) );
	};

	return (
		<div className="data-source-list-wrapper">
			<table className="table data-source-list">
				<thead className="table-header">
					<tr>
						<th>{ __( 'Slug', 'remote-data-blocks' ) }</th>
						<th>{ __( 'Service', 'remote-data-blocks' ) }</th>
						<th>{ __( 'Meta', 'remote-data-blocks' ) }</th>
						<th className="data-source-actions">{ __( 'Actions', 'remote-data-blocks' ) }</th>
					</tr>
				</thead>
				<tbody className="table-body">
					{ getValidDataSources().map( source => {
						const { uuid, slug, service } = source;
						return (
							<tr key={ uuid } className="table-row">
								<td>
									<Text className="data-source-slug">{ slug }</Text>
								</td>
								<td>
									<Text>{ toTitleCase( service ) }</Text>
								</td>
								<td> { renderDataSourceMeta( source ) } </td>
								<td className="data-source-actions">
									<ButtonGroup className="data-source-actions">
										<Button variant="secondary" onClick={ () => onEditDataSource( uuid ) }>
											<Icon icon={ edit } />
										</Button>
										<Button variant="secondary" onClick={ () => onDeleteDataSource( source ) }>
											<Icon icon={ trash } />
										</Button>
									</ButtonGroup>
								</td>
							</tr>
						);
					} ) }
				</tbody>
			</table>

			{ dataSourceToDelete && (
				<Modal
					className="confirm-delete-data-source-modal"
					title="Delete Data Source"
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
						Are you sure you want to delete
						<strong> &ldquo;{ toTitleCase( dataSourceToDelete.service ) }&rdquo; </strong>
						data source with slug
						<strong> &ldquo;{ dataSourceToDelete.slug }&rdquo;</strong>?
					</p>

					<div className="action-buttons">
						<Button variant="link" onClick={ onCancelDeleteDialog }>
							Cancel
						</Button>
						<Button
							variant="primary"
							isDestructive
							onClick={ () => void onConfirmDeleteDataSource( dataSourceToDelete ) }
						>
							Confirm
						</Button>
					</div>
				</Modal>
			) }
		</div>
	);
};

export default DataSourceList;
