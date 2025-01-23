import { useInstanceId } from '@wordpress/compose';
import { DataViews, View } from '@wordpress/dataviews/wp';
import { useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { usePatterns } from '@/blocks/remote-data-container/hooks/usePatterns';

interface ItemListProps {
	blockName: string;
	fetchNextPage: () => Promise< void >;
	fetchPreviousPage: () => Promise< void >;
	loading: boolean;
	onSelect: ( data: RemoteDataQueryInput ) => void;
	results?: RemoteDataResult[];
	searchTerms: string;
	setSearchTerms: ( newValue: string ) => void;
	supportsSearch: boolean;
	totalItems?: number;
}

export function ItemList( props: ItemListProps ) {
	const {
		blockName,
		fetchNextPage,
		fetchPreviousPage,
		loading,
		onSelect,
		results,
		searchTerms,
		setSearchTerms,
		supportsSearch,
		totalItems,
	} = props;
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
					.filter( ( key: string ) => ! /(^|_)(id)$/i.test( key ) ) // Filters out keys containing 'id' or similar patterns
			)
		);

		// generic search for title
		const title: string =
			getFields.find(
				( field: string ) =>
					field.toLowerCase().includes( 'title' ) || field.toLowerCase().includes( 'name' )
			) || '';

		// generic search for media
		const media: string =
			getFields.find(
				( field: string ) =>
					field.toLowerCase().includes( 'url' ) || field.toLowerCase().includes( 'image' )
			) || '';

		const fieldObject: {
			id: string;
			label: string;
			enableGlobalSearch: boolean;
			render?: ( { item }: { item: RemoteDataResult } ) => JSX.Element;
			enableSorting: boolean;
		}[] = getFields.map( field => {
			return {
				id: field,
				label: field ?? '',
				enableGlobalSearch: true,
				getValue: ( { item }: { item: RemoteDataResult } ) => item[ field ] as string,
				render:
					field === media
						? ( { item }: { item: RemoteDataResult } ) => {
								return (
									<img
										// temporary until we pull in more data
										alt=""
										src={ item[ field ] as string }
									/>
								);
						  }
						: undefined,
				enableSorting: field !== media,
			};
		} );

		return { fields: fieldObject, tableFields: getFields, titleField: title, mediaField: media };
	}, [ data ] );

	const [ view, setView ] = useState< View >( {
		type: 'table' as const,
		perPage: data.length,
		page: 1,
		search: '',
		fields: [],
		filters: [],
		layout: {},
		titleField,
		mediaField,
	} );

	function onChangeView( newView: View ) {
		if ( newView.search !== searchTerms ) {
			setSearchTerms( newView.search ?? '' );
		}

		console.log( { view, newView } );

		const currentPage = view.page ?? 1;
		let newPage = newView.page ?? 1;

		// Only allow incrementing or decrementing the page by 1 at a time.
		if ( newPage > currentPage ) {
			newPage = currentPage + 1;
			void fetchNextPage();
		} else if ( newPage < currentPage ) {
			newPage = currentPage - 1;
			void fetchPreviousPage();
		}

		setView( {
			...newView,
			fields: tableFields.filter( field => field !== mediaField ),
			page: newPage,
		} );
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
			isLoading={ loading || ! pattern || ! results || results.length === 0 }
			isItemClickable={ () => true }
			onClickItem={ item => onSelect( item ) }
			onChangeView={ onChangeView }
			paginationInfo={ {
				totalItems: totalItems ?? data.length,
				totalPages: Math.ceil( ( totalItems ?? data.length ) / Math.max( 1, data.length ) ),
			} }
			search={ supportsSearch }
			view={ view }
		/>
	);
}
