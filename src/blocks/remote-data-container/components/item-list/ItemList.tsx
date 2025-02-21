import { BaseControl, Button, __experimentalHStack as HStack } from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { Action, DataViews, View } from '@wordpress/dataviews/wp';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { ItemListField } from '@/blocks/remote-data-container/components/item-list/ItemListField';
import { usePatterns } from '@/blocks/remote-data-container/hooks/usePatterns';
import { removeNullValuesFromObject } from '@/utils/type-narrowing';

function getResultsWithId( results: RemoteDataResult[], instanceId: string ): RemoteDataResult[] {
	return ( results ?? [] ).map( ( result: RemoteDataResult ) => {
		const parsedItem = removeNullValuesFromObject( result );

		if ( parsedItem.id ) {
			return parsedItem;
		}

		// ensure each result has an 'id' key
		const idKey = Object.keys( parsedItem ).find( key => /(^|_)(id)$/i.test( key ) );
		return {
			...parsedItem,
			id: idKey ? parsedItem[ idKey ] : instanceId,
		};
	} );
}

interface ItemListProps {
	availableBindings: Record< string, RemoteDataBinding >;
	blockName: string;
	loading: boolean;
	onSelect: ( data: RemoteDataQueryInput ) => void;
	onSelectField?: ( data: FieldSelection, fieldValue: string ) => void;
	page: number;
	perPage?: number;
	remoteData?: RemoteData;
	searchInput: string;
	setPage: ( newPage: number ) => void;
	setSearchInput: ( newValue: string ) => void;
	supportsBulk: boolean;
	supportsSearch: boolean;
	totalItems?: number;
	totalPages?: number;
}

export function ItemList( props: ItemListProps ) {
	const {
		availableBindings,
		blockName,
		loading,
		onSelect,
		onSelectField,
		page,
		perPage,
		remoteData,
		searchInput,
		setPage,
		setSearchInput,
		supportsBulk,
		supportsSearch,
		totalItems,
		totalPages,
	} = props;
	const { defaultPattern: pattern } = usePatterns( blockName );
	const instanceId = useInstanceId( ItemList, blockName );
	const [ selectedItems, setSelectedItems ] = useState< string[] >( [] );

	const results = remoteData?.results ?? [];
	const data = loading ? [] : getResultsWithId( results ?? [], instanceId );

	// get fields from results data to use as columns
	const fieldNames: string[] = Array.from(
		new Set(
			data
				?.flatMap( item => Object.keys( item ) )
				.filter(
					key => key in availableBindings && availableBindings[ key ]?.type !== 'id' // filter out ID fields to hide from table
				)
		)
	);

	// Find title field from availableBindings by checking type
	const titleField = Object.entries( availableBindings ).find(
		( [ _, binding ] ) => binding.type === 'string' && binding.name.toLowerCase() === 'title'
	)?.[ 0 ];

	// Find media field from availableBindings by checking type
	const mediaField = Object.entries( availableBindings ).find(
		( [ _, binding ] ) => binding.type === 'image_url'
	)?.[ 0 ];

	// Find the ID field from availableBindings
	const idField =
		Object.entries( availableBindings ).find(
			( [ _, binding ] ) => binding.type === 'id'
		)?.[ 0 ] ?? 'id';

	const fields = fieldNames.map( field => ( {
		id: field,
		label: availableBindings[ field ]?.name ?? field,
		enableGlobalSearch: true,
		getValue: ( { item }: { item: RemoteDataResult } ) => item[ field ]?.toString() ?? '',
		render: ( { item }: { item: RemoteDataResult } ) => (
			<ItemListField
				blockName={ blockName }
				field={ field }
				item={ item }
				mediaField={ mediaField }
				onSelect={ onSelect }
				onSelectField={ onSelectField }
				remoteData={ remoteData }
			/>
		),
		enableSorting: field !== mediaField,
	} ) );

	// hide media and title fields from table view if defined to avoid duplication
	const tableFields = fieldNames.filter( field => field !== mediaField && field !== titleField );

	const [ view, setView ] = useState< View & { selection: string[] } >( {
		type: 'table' as const,
		perPage: perPage ?? data.length,
		page,
		search: searchInput,
		fields: tableFields,
		filters: [],
		layout: {},
		titleField,
		mediaField,
		selection: selectedItems,
	} );

	function onChangeView( newView: View ) {
		setPage( newView.page ?? 1 );
		setSearchInput( newView.search ?? '' );
		setView( { ...newView, selection: selectedItems } );
	}

	// Temporary helper to handle pagination and bulk selection
	const onChangeSelection = ( newIds: string[] ) => {
		// Get all currently selected IDs from the view
		const currentPageIds = data.map( item => item.id.toString() );

		// Keep selections from other pages that aren't in the current view
		const otherPageSelections = selectedItems.filter( id => ! currentPageIds.includes( id ) );

		// Combine selections from other pages with new selections
		setSelectedItems( [ ...otherPageSelections, ...newIds ] );
	};

	const chooseItemAction = {
		id: 'choose',
		icon: <>{ __( 'Choose' ) }</>,
		isPrimary: true,
		label: '',
		callback: () => {
			const ids = selectedItems.join( ',' );
			return onSelect( { [ idField ]: ids } );
		},
		supportsBulk,
	};
	const actions: Action< RemoteDataResult >[] = onSelectField ? [] : [ chooseItemAction ];

	const itemCountLabel =
		selectedItems.length > 1 ? __( 'items selected in total' ) : __( 'item selected in total' );

	return (
		<>
			<DataViews< RemoteDataResult >
				actions={ actions }
				data={ data }
				defaultLayouts={ { table: {} } }
				header={
					supportsBulk &&
					selectedItems.length > 0 &&
					! loading && (
						<HStack justify="start" style={ { marginRight: '16px' } }>
							<BaseControl
								className="rdb-dataviews-bulk-actions-footer__item-count-total"
								__nextHasNoMarginBottom
							>
								<BaseControl.VisualLabel style={ { marginBottom: '0' } }>
									{ selectedItems.length } { itemCountLabel }
								</BaseControl.VisualLabel>
							</BaseControl>
							<Button onClick={ () => setSelectedItems( [] ) } variant="tertiary">
								{ __( 'Cancel All' ) }
							</Button>
							<Button
								onClick={ () => onSelect( { [ idField ]: selectedItems.join( ',' ) } ) }
								variant="tertiary"
							>
								{ __( 'Choose' ) }
							</Button>
						</HStack>
					)
				}
				fields={ fields }
				getItemId={ ( item: { id?: string } ) => item.id || '' }
				isLoading={ loading || ! pattern || ! results }
				isItemClickable={ () => true }
				onClickItem={ item => onSelect( item ) }
				onChangeSelection={ onChangeSelection }
				onChangeView={ onChangeView }
				paginationInfo={ {
					totalItems: totalItems ?? data.length,
					totalPages: totalPages ?? 1,
				} }
				search={ supportsSearch }
				selection={ selectedItems }
				view={ view }
			/>
		</>
	);
}
