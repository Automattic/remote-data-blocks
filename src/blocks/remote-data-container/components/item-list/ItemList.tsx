import { useInstanceId } from '@wordpress/compose';
import { DataViews, filterSortAndPaginate, View } from '@wordpress/dataviews/wp';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { usePatterns } from '@/blocks/remote-data-container/hooks/usePatterns';

interface ItemListProps {
	availableBindings: Record< string, RemoteDataBinding >;
	blockName: string;
	loading: boolean;
	onSelect: ( data: RemoteDataQueryInput ) => void;
	results?: RemoteDataResult[];
	searchTerms: string;
	setSearchTerms: ( newValue: string ) => void;
}

export function ItemList( props: ItemListProps ) {
	const { availableBindings, blockName, loading, onSelect, results, searchTerms, setSearchTerms } =
		props;
	const { defaultPattern: pattern } = usePatterns( blockName );

	const instanceId = useInstanceId( ItemList, blockName );

	const data = useMemo( () => {
		// remove null values from the data to prevent errors in filterSortAndPaginate
		const removeNullValues = ( obj: Record< string, unknown > ): Record< string, unknown > => {
			return Object.fromEntries(
				Object.entries( obj ).filter( ( [ _, value ] ) => value !== null )
			);
		};

		return ( results ?? [] ).map( ( item: Record< string, unknown > ) => {
			const parsedItem = removeNullValues( item );

			if ( parsedItem.id ) {
				return parsedItem;
			}

			// ensure each result has an 'id' key
			const idKey = Object.keys( parsedItem ).find( key => /(^|_)(id)$/i.test( key ) );
			return {
				...parsedItem,
				id: idKey ? parsedItem[ idKey ] : instanceId,
			};
		} ) as RemoteDataResult[];
	}, [ results ] );

	// get fields from results data to use as columns
	const { fields, mediaField, tableFields, titleField } = useMemo( () => {
		const getFields: string[] = Array.from(
			new Set(
				data
					?.flatMap( item => Object.keys( item ) )
					.filter(
						key => key in availableBindings && availableBindings[ key ]?.type !== 'id' // filter out ID fields to hide from table
					)
			)
		);

		// Find title field from availableBindings by checking type
		const title = Object.entries( availableBindings ).find(
			( [ _, binding ] ) => binding.type === 'string' && binding.name.toLowerCase() === 'title'
		)?.[ 0 ];

		// Find media field from availableBindings by checking type
		const media = Object.entries( availableBindings ).find(
			( [ _, binding ] ) => binding.type === 'image_url'
		)?.[ 0 ];

		const fieldObject = getFields.map( field => {
			return {
				id: field,
				label: availableBindings[ field ]?.name ?? field,
				enableGlobalSearch: true,
				getValue: ( { item }: { item: RemoteDataResult } ) => item[ field ] as string,
				render:
					field === media
						? ( { item }: { item: RemoteDataResult } ) => {
								return (
									<img alt={ ( item.image_alt as string ) ?? '' } src={ item[ field ] as string } />
								);
						  }
						: undefined,
				enableSorting: field !== media,
			};
		} );

		return { fields: fieldObject, tableFields: getFields, titleField: title, mediaField: media };
	}, [ availableBindings, data ] );

	const [ view, setView ] = useState< View >( {
		type: 'table' as const,
		perPage: 8,
		page: 1,
		search: '',
		fields: [],
		filters: [],
		layout: {},
		titleField,
		mediaField,
	} );

	const defaultLayouts = mediaField
		? {
				table: {},
				grid: {},
		  }
		: { table: {} };

	// this prevents just an empty table rendering
	useEffect( () => {
		if ( tableFields.length > 0 ) {
			setView( prevView => ( {
				...prevView,
				// hide media and title fields from table view if defined to avoid duplication
				fields: tableFields.filter( field => field !== mediaField && field !== titleField ),
			} ) );
		}
	}, [ mediaField, tableFields, titleField ] );

	useEffect( () => {
		if ( view.search !== searchTerms ) {
			setSearchTerms( view.search ?? '' );
		}
	}, [ view, searchTerms ] );

	// filter, sort and paginate data
	const { data: filteredData, paginationInfo } = useMemo( () => {
		return filterSortAndPaginate( data ?? [], view, fields );
	}, [ data, view ] );

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
			data={ filteredData }
			defaultLayouts={ defaultLayouts }
			fields={ fields }
			getItemId={ ( item: { id?: string } ) => item.id || '' }
			isLoading={ loading || ! pattern || ! results || results.length === 0 }
			isItemClickable={ () => true }
			onClickItem={ item => onSelect( item ) }
			onChangeView={ setView }
			paginationInfo={ paginationInfo }
			view={ view }
		/>
	);
}
