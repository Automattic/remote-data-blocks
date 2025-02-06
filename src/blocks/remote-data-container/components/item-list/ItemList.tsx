import { useInstanceId } from '@wordpress/compose';
import { DataViews, View } from '@wordpress/dataviews/wp';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { usePatterns } from '@/blocks/remote-data-container/hooks/usePatterns';
import { removeNullValuesFromObject } from '@/utils/type-narrowing';

interface ItemListProps {
	availableBindings: Record< string, RemoteDataBinding >;
	blockName: string;
	loading: boolean;
	onSelect: ( data: RemoteDataQueryInput ) => void;
	page: number;
	perPage?: number;
	results?: RemoteDataResult[];
	searchInput: string;
	setPage: ( newPage: number ) => void;
	setSearchInput: ( newValue: string ) => void;
	supportsSearch: boolean;
	totalItems?: number;
	totalPages?: number;
}

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

export function ItemList( props: ItemListProps ) {
	const {
		availableBindings,
		blockName,
		loading,
		onSelect,
		page,
		perPage,
		results,
		searchInput,
		setPage,
		setSearchInput,
		supportsSearch,
		totalItems,
		totalPages,
	} = props;
	const { defaultPattern: pattern } = usePatterns( blockName );

	const instanceId = useInstanceId( ItemList, blockName );
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

	const fields = fieldNames.map( field => ( {
		id: field,
		label: availableBindings[ field ]?.name ?? field,
		enableGlobalSearch: true,
		getValue: ( { item }: { item: RemoteDataResult } ) => item[ field ] as string,
		render:
			field === mediaField
				? ( { item }: { item: RemoteDataResult } ) => {
						return (
							<img alt={ ( item.image_alt as string ) ?? '' } src={ item[ field ] as string } />
						);
				  }
				: undefined,
		enableSorting: field !== mediaField,
	} ) );

	// hide media and title fields from table view if defined to avoid duplication
	const tableFields = fieldNames.filter( field => field !== mediaField && field !== titleField );

	const [ view, setView ] = useState< View >( {
		type: 'table' as const,
		perPage: perPage ?? data.length,
		page,
		search: searchInput,
		fields: tableFields,
		filters: [],
		layout: {},
		titleField,
		mediaField,
	} );

	function onChangeView( newView: View ) {
		setPage( newView.page ?? 1 );
		setSearchInput( newView.search ?? '' );

		setView( newView );
	}

	const defaultLayouts = mediaField
		? {
				table: {},
				grid: {},
		  }
		: { table: {} };

	const actions = [
		{
			id: 'choose',
			icon: <>{ __( 'Choose' ) }</>,
			isPrimary: true,
			label: '',
			callback: ( items: RemoteDataResult[] ) => {
				items.map( item => onSelect( item ) );
			},
		},
	];

	return (
		<DataViews
			actions={ actions }
			data={ data }
			defaultLayouts={ defaultLayouts }
			fields={ fields }
			getItemId={ ( item: { id?: string } ) => item.id || '' }
			isLoading={ loading || ! pattern || ! results }
			isItemClickable={ () => true }
			onClickItem={ item => onSelect( item ) }
			onChangeView={ onChangeView }
			paginationInfo={ {
				totalItems: totalItems ?? data.length,
				totalPages: totalPages ?? 1,
			} }
			search={ supportsSearch }
			view={ view }
		/>
	);
}
