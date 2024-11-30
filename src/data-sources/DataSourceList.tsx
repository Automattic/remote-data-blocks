import { __experimentalConfirmDialog as ConfirmDialog, Placeholder } from '@wordpress/components';
import { DataViews, filterSortAndPaginate, View } from '@wordpress/dataviews';
import { useMemo, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { info } from '@wordpress/icons';

import { SUPPORTED_SERVICES, SUPPORTED_SERVICES_LABELS } from './constants';
import { useDataSources } from '@/data-sources/hooks/useDataSources';
import { DataSourceConfig } from '@/data-sources/types';
import { useSettingsContext } from '@/settings/hooks/useSettingsNav';

import './DataSourceList.scss';

const DataSourceList = () => {
	const { dataSources, loadingDataSources, deleteDataSource, fetchDataSources } = useDataSources();
	const [ dataSourceToDelete, setDataSourceToDelete ] = useState< DataSourceConfig | null >( null );
	const { pushState } = useSettingsContext();

	const [ view, setView ] = useState< View >( {
		type: 'table',
		perPage: 10,
		page: 1,
		search: '',
		fields: [ 'slug', 'service', 'meta' ],
		filters: [],
		layout: {},
	} );

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

	const fields = useMemo(
		() => [
			{ id: 'slug', label: __( 'Slug', 'remote-data-blocks' ), enableGlobalSearch: true },
			{
				id: 'service',
				label: __( 'Data Source', 'remote-data-blocks' ),
				enableGlobalSearch: true,
			},
			{
				id: 'meta',
				label: __( 'Meta', 'remote-data-blocks' ),
				enableGlobalSearch: true,
				render: ( { item }: { item: DataSourceConfig } ) => renderDataSourceMeta( item ),
			},
		],
		[]
	);

	// filter, sort and paginate data
	const { data: shownData, paginationInfo } = useMemo( () => {
		return filterSortAndPaginate( dataSources, view, fields );
	}, [ dataSources, fields, view ] );

	const defaultLayouts = {
		table: {
			layout: {
				primaryField: 'id',
			},
		},
	};

	const actions = [
		{
			id: 'edit',
			label: __( 'Edit', 'remote-data-blocks' ),
			icon: 'edit',
			isPrimary: true,
			callback: ( [ item ]: DataSourceConfig[] ) => {
				if ( item ) {
					onEditDataSource( item.uuid );
				}
			},
			isEligible: ( item: DataSourceConfig ) => {
				// @ts-expect-error some examples have a service of 'unknown'
				return item ? item.service !== 'unknown' : false;
			},
		},
		{
			id: 'delete',
			label: __( 'Delete', 'remote-data-blocks' ),
			icon: 'trash',
			isDestructive: true,
			callback: ( [ item ]: DataSourceConfig[] ) => {
				if ( item ) {
					onDeleteDataSource( item );
				}
			},
			isEligible: ( item: DataSourceConfig ) => {
				// @ts-expect-error some examples have a service of 'unknown'
				return item ? item.service !== 'unknown' : false;
			},
		},
	];

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
		<>
			<DataViews
				actions={ actions }
				data={ shownData }
				fields={ fields }
				view={ view }
				onChangeView={ setView }
				paginationInfo={ paginationInfo }
				defaultLayouts={ defaultLayouts }
				getItemId={ ( item: DataSourceConfig ) => item.uuid }
				isLoading={ loadingDataSources }
			/>
			{ dataSourceToDelete && (
				<ConfirmDialog
					confirmButtonText={ __( 'Confirm', 'remote-data-blocks' ) }
					onCancel={ () => onCancelDeleteDialog() }
					onConfirm={ () => void onConfirmDeleteDataSource( dataSourceToDelete ) }
					size="medium"
					title={ __( 'Delete Data Source', 'remote-data-blocks' ) }
				>
					{ sprintf(
						__(
							'Are you sure you want to delete "%s" data source with slug "%s"?',
							'remote-data-blocks'
						),
						getServiceLabel( dataSourceToDelete.service ),
						dataSourceToDelete.slug
					) }
				</ConfirmDialog>
			) }
		</>
	);
};

export default DataSourceList;
