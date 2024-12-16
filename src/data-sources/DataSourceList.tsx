import {
	__experimentalConfirmDialog as ConfirmDialog,
	Icon,
	Placeholder,
} from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { DataViews, filterSortAndPaginate, type View } from '@wordpress/dataviews/wp';
import { useMemo, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { chevronRightSmall, info } from '@wordpress/icons';
import { store as noticesStore, NoticeStoreActions, WPNotice } from '@wordpress/notices';

import { SUPPORTED_SERVICES, SUPPORTED_SERVICES_LABELS } from './constants';
import { useDataSources } from '@/data-sources/hooks/useDataSources';
import { DataSourceConfig } from '@/data-sources/types';
import { useSettingsContext } from '@/settings/hooks/useSettingsNav';
import './DataSourceList.scss';
import { AirtableIcon } from '@/settings/icons/AirtableIcon';
import { GoogleSheetsIcon } from '@/settings/icons/GoogleSheetsIcon';
import HttpIcon from '@/settings/icons/HttpIcon';
import { ShopifyIcon } from '@/settings/icons/ShopifyIcon';

const DataSourceList = () => {
	const { createSuccessNotice, createErrorNotice } =
		useDispatch< NoticeStoreActions >( noticesStore );
	const { dataSources, loadingDataSources, deleteDataSource, fetchDataSources } = useDataSources();
	const [ dataSourceToDelete, setDataSourceToDelete ] = useState<
		DataSourceConfig | DataSourceConfig[] | null
	>( null );
	const { pushState } = useSettingsContext();

	const onCancelDeleteDialog = () => {
		setDataSourceToDelete( null );
	};

	const onDeleteDataSource = ( source: DataSourceConfig | DataSourceConfig[] ) =>
		setDataSourceToDelete( source );

	const onEditDataSource = ( uuidToEdit: string ) => {
		const newUrl = new URL( window.location.href );
		newUrl.searchParams.set( 'editDataSource', uuidToEdit );
		pushState( newUrl );
	};

	const onConfirmDeleteDataSource = async ( source: DataSourceConfig | DataSourceConfig[] ) => {
		const sources = Array.isArray( source ) ? source : [ source ];
		await Promise.all( sources.map( src => deleteDataSource( src ).catch( () => null ) ) );
		setDataSourceToDelete( null );
		await fetchDataSources().catch( () => null );
	};

	const renderDataSourceMeta = ( source: DataSourceConfig ) => {
		const tags = [];
		switch ( source.service ) {
			case 'airtable':
				tags.push( {
					key: 'base',
					primaryValue: source.base?.name,
					secondaryValue: source.tables?.[ 0 ]?.name,
				} );
				break;
			case 'shopify':
				tags.push( { key: 'store', primaryValue: source.store_name } );
				break;
			case 'google-sheets':
				tags.push( {
					key: 'spreadsheet',
					primaryValue: source.spreadsheet.name,
					secondaryValue: source.sheet.name,
				} );
				break;
		}

		return tags.filter( Boolean ).map( tag => (
			<span key={ tag.key } className="data-source-meta">
				{ tag.primaryValue }
				{ tag.secondaryValue && (
					<>
						<Icon
							icon={ chevronRightSmall }
							style={ { fill: '#949494', verticalAlign: 'middle' } }
						/>
						{ tag.secondaryValue }
					</>
				) }
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
	const getServiceIcon = ( service: ( typeof SUPPORTED_SERVICES )[ number ] ) => {
		switch ( service ) {
			case 'airtable':
				return AirtableIcon;
			case 'shopify':
				return ShopifyIcon;
			case 'google-sheets':
				return GoogleSheetsIcon;
			case 'generic-http':
				return HttpIcon;
			default:
				return null;
		}
	};

	const [ view, setView ] = useState< View >( {
		type: 'table',
		perPage: 10,
		page: 1,
		search: '',
		fields: [ 'display_name', 'service', 'meta' ],
		filters: [],
		layout: {},
	} );

	const fields = useMemo(
		() => [
			{
				id: 'display_name',
				label: __( 'Source', 'remote-data-blocks' ),
				enableGlobalSearch: true,
				render: ( { item }: { item: DataSourceConfig } ) => {
					const serviceIcon = getServiceIcon( item.service );
					return (
						<>
							{ serviceIcon && (
								<Icon
									icon={ serviceIcon }
									style={ { marginRight: '16px', verticalAlign: 'text-bottom' } }
								/>
							) }
							{ item.display_name }
						</>
					);
				},
			},
			{
				id: 'service',
				label: __( 'Service', 'remote-data-blocks' ),
				enableGlobalSearch: true,
				elements: SUPPORTED_SERVICES.map( service => ( {
					value: service,
					label: getServiceLabel( service ),
				} ) ),
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
		table: {},
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
				return item ? SUPPORTED_SERVICES.includes( item.service ) : false;
			},
		},
		{
			id: 'copy',
			label: __( 'Copy UUID', 'remote-data-blocks' ),
			icon: 'copy',
			isPrimary: true,
			callback: ( [ item ]: DataSourceConfig[] ) => {
				if ( item && item.uuid ) {
					navigator.clipboard
						.writeText( item.uuid )
						.then( () => {
							showSnackbar(
								'success',
								__( 'Copied data source UUID to the clipboard.', 'remote-data-blocks' )
							);
						} )
						.catch( () =>
							showSnackbar( 'error', __( 'Failed to copy to clipboard.', 'remote-data-blocks' ) )
						);
				}
			},
		},
		{
			id: 'delete',
			label: __( 'Delete', 'remote-data-blocks' ),
			icon: 'trash',
			isDestructive: true,
			callback: ( items: DataSourceConfig[] ) => {
				if ( items.length === 1 ) {
					if ( items[ 0 ] ) {
						onDeleteDataSource( items[ 0 ] );
					}
				} else if ( items.length > 1 ) {
					onDeleteDataSource( items );
				}
			},
			isEligible: ( item: DataSourceConfig ) => {
				return item ? SUPPORTED_SERVICES.includes( item.service ) : false;
			},
			supportsBulk: true,
		},
	];

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
					{ Array.isArray( dataSourceToDelete )
						? __(
								'Are you sure you want to delete the selected data sources?',
								'remote-data-blocks'
						  )
						: sprintf(
								__( 'Are you sure you want to delete %s data source "%s"?', 'remote-data-blocks' ),
								getServiceLabel( dataSourceToDelete.service ),
								dataSourceToDelete.display_name
						  ) }
				</ConfirmDialog>
			) }
		</>
	);
};

export default DataSourceList;
