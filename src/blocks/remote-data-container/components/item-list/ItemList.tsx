import { Action, DataViews, View } from '@wordpress/dataviews/wp';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { ItemListField } from '@/blocks/remote-data-container/components/item-list/ItemListField';
import { usePatterns } from '@/blocks/remote-data-container/hooks/usePatterns';
import { getRemoteDataResultValue } from '@/utils/remote-data';
import { ID_FIELD_TYPES, IMAGE_ALT_FIELD_TYPES } from '../../config/constants';

interface ItemListProps {
	availableBindings: Record< string, RemoteDataBinding >;
	blockName: string;
	loading: boolean;
	onSelect?: ( ids: string[] ) => void;
	onSelectField?: ( data: FieldSelection, fieldValue: string ) => void;
	page: number;
	perPage?: number;
	results?: RemoteDataApiResult[];
	searchInput: string;
	selectionIds: string[];
	setPage: ( newPage: number ) => void;
	setSearchInput: ( newValue: string ) => void;
	setSelectionIds: ( ids: string[] ) => void;
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
		results = [],
		searchInput,
		selectionIds,
		setPage,
		setSearchInput,
		setSelectionIds,
		supportsSearch,
		totalItems,
		totalPages,
	} = props;
	const { defaultPattern: pattern } = usePatterns( blockName );

	// get fields from results data to use as columns
	const fieldNames: string[] = Array.from(
		new Set(
			results
				.flatMap( item => Object.keys( item.result ) )
				.filter(
					// TODO
					key =>
						availableBindings[ key ] &&
						! ID_FIELD_TYPES.includes( availableBindings[ key ]?.type ?? '' ) // filter out ID fields to hide from table
				)
		)
	);

	// Find title field from availableBindings by checking type
	const titleField = Object.entries( availableBindings ).find(
		( [ _, binding ] ) => binding.type === 'string' && binding.name.toLowerCase() === 'title'
	)?.[ 0 ];

	// Find media field from availableBindings by checking type
	const mediaField = Object.entries( availableBindings ).find( ( [ _, binding ] ) =>
		IMAGE_ALT_FIELD_TYPES.includes( binding.type )
	)?.[ 0 ];

	const fields = fieldNames.map( field => ( {
		id: field,
		label: availableBindings[ field ]?.name ?? field,
		enableGlobalSearch: true,
		getValue: ( { item }: { item: RemoteDataApiResult } ) =>
			getRemoteDataResultValue( item, field ),
		render: ( { item }: { item: RemoteDataApiResult } ) => (
			<ItemListField
				blockName={ blockName }
				field={ field }
				item={ item }
				mediaField={ mediaField }
				onSelectField={ onSelectField }
			/>
		),
		enableSorting: field !== mediaField,
	} ) );

	// hide media and title fields from table view if defined to avoid duplication
	const tableFields = fieldNames.filter( field => field !== mediaField && field !== titleField );

	const [ view, setView ] = useState< View & { selectionIds: string[] } >( {
		type: 'table' as const,
		perPage: perPage ?? results.length,
		page,
		search: searchInput,
		fields: tableFields,
		filters: [],
		layout: {},
		titleField,
		mediaField,
		selectionIds,
	} );

	function onChangeView( newView: View ) {
		setPage( newView.page ?? 1 );
		setSearchInput( newView.search ?? '' );
		setView( { ...newView, selectionIds } );
	}

	const defaultLayouts = mediaField
		? {
				table: {},
				grid: {},
		  }
		: { table: {} };

	// Temporary helper to handle pagination and bulk selection
	const onChangeSelection = ( newIds: string[] ) => {
		setSelectionIds( Array.from( new Set< string >( [ ...newIds, ...selectionIds ] ) ) );
	};

	const chooseItemAction = {
		id: 'choose',
		icon: <>{ __( 'Choose' ) }</>,
		isPrimary: true,
		label: '',
		callback: ( items: RemoteDataApiResult[] ) => {
			onSelect?.( items.map( item => item.uuid ) );
		},
		supportsBulk: true,
	};
	const actions: Action< RemoteDataApiResult >[] = onSelectField ? [] : [ chooseItemAction ];

	return (
		<>
			<DataViews< RemoteDataApiResult >
				actions={ actions }
				data={ results }
				defaultLayouts={ defaultLayouts }
				fields={ fields }
				getItemId={ ( item: RemoteDataApiResult ) => item.uuid }
				isLoading={ loading || ! pattern || ! results }
				isItemClickable={ () => true }
				onChangeSelection={ onChangeSelection }
				onChangeView={ onChangeView }
				paginationInfo={ {
					totalItems: totalItems ?? results.length,
					totalPages: totalPages ?? 1,
				} }
				search={ supportsSearch }
				selection={ selectionIds }
				view={ view }
			/>
		</>
	);
}
