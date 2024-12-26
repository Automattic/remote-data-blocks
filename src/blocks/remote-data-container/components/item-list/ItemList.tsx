import { useInstanceId } from '@wordpress/compose';
import { DataViews, filterSortAndPaginate, View } from '@wordpress/dataviews/wp';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { usePatterns } from '@/blocks/remote-data-container/hooks/usePatterns';

interface ItemListProps {
	blockName: string;
	loading: boolean;
	onSelect: ( data: RemoteDataQueryInput ) => void;
	results?: RemoteData[ 'results' ];
	searchTerms: string;
	setSearchTerms: ( newValue: string ) => void;
}

export function ItemList( props: ItemListProps ) {
	const { blockName, loading, onSelect, results, searchTerms, setSearchTerms } = props;
	const { defaultPattern: pattern } = usePatterns( blockName );

	const instanceId = useInstanceId( ItemList, blockName );

	// ensure each result has an 'id' key
	const data = useMemo( () => {
		return ( results ?? [] ).map( ( item: Record< string, unknown > ) =>
			item.id
				? item
				: {
						...item,
						id: Object.keys( item ).find( key => /(^|_)(id)$/i.test( key ) ) // Regex to match 'id' or part of '_id'
							? item[ Object.keys( item ).find( key => /(^|_)(id)$/i.test( key ) ) as string ]
							: instanceId,
				  }
		) as RemoteData[ 'results' ];
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
			render?: ( { item }: { item: RemoteData[ 'results' ][ 0 ] } ) => JSX.Element;
			enableSorting: boolean;
		}[] = getFields.map( field => {
			return {
				id: field,
				label: field ?? '',
				enableGlobalSearch: true,
				render:
					field === media
						? ( { item }: { item: RemoteData[ 'results' ][ 0 ] } ) => {
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
				fields: tableFields.filter( field => field !== mediaField ),
			} ) );
		}
	}, [ mediaField, tableFields ] );

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
			callback: ( items: RemoteData[ 'results' ] ) => {
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
