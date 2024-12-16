import { useInstanceId } from '@wordpress/compose';
import { DataViews, filterSortAndPaginate, Operator, View } from '@wordpress/dataviews/wp';
import { useEffect, useMemo, useState } from '@wordpress/element';

import { usePatterns } from '@/blocks/remote-data-container/hooks/usePatterns';
import { __ } from '@/utils/i18n';

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
	const tableFields = useMemo( () => Array.from( new Set( results?.flatMap( Object.keys ) ) ), [] );

	// generic search for title
	const titleField =
		tableFields.find(
			field => field.toLowerCase().includes( 'title' ) || field.toLowerCase().includes( 'name' )
		) || '';

	// generic search for media
	const mediaField =
		tableFields.find(
			field => field.toLowerCase().includes( 'url' ) || field.toLowerCase().includes( 'image' )
		) || '';

	const [ view, setView ] = useState< View >( {
		type: 'table' as const,
		perPage: 10,
		page: 1,
		search: '',
		fields: tableFields.filter(
			field => field !== mediaField && field !== titleField && ! /(^|_)(id)$/i.test( field )
		),
		filters: [],
		layout: {},
		titleField,
		mediaField,
	} );

	const fields = tableFields.map( field => {
		// merge duplicate fields for filters
		const mergedDuplicateFields = Array.from(
			new Set(
				results
					?.map( result => result[ field ] )
					.filter( value => value !== '' && value !== undefined )
			)
		);

		return {
			id: field,
			label: field ?? '',
			enableGlobalSearch: true,
			elements: mergedDuplicateFields.map( value => ( {
				label: value ?? '',
				value: value ?? '',
			} ) ),
			render:
				field === mediaField
					? ( { item }: { item: Record< string, string > } ) => {
							return (
								<img
									alt={
										Object.prototype.hasOwnProperty.call( item, titleField )
											? item[ titleField ]
											: ''
									}
									src={
										typeof item[ field ] === 'string' &&
										/^(https?|data:image\/)/.test( item[ field ] ) &&
										( item[ field ].startsWith( 'http' ) ||
											item[ field ].startsWith( 'data:image/' ) )
											? item[ field ]
											: ''
									}
								/>
							);
					  }
					: undefined,
			enableSorting: field !== mediaField,
			filterBy: {
				operators: [ 'isAny', 'isNone', 'isAll', 'isNotAll' ] as Operator[],
			},
		};
	} );

	const defaultLayouts = {
		table: {},
		grid: {},
		list: {},
	};

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
			id: 'select',
			label: __( 'Select item' ),
			callback: ( items: RemoteData[ 'results' ] ) => {
				items.map( item => onSelect( item ) );
			},
		},
	];

	return (
		<DataViews
			actions={ actions }
			data={ filteredData }
			fields={ fields }
			view={ view }
			onChangeView={ setView }
			paginationInfo={ paginationInfo }
			defaultLayouts={ defaultLayouts }
			isLoading={ loading || ! pattern }
			getItemId={ ( item: { id?: string } ) => item.id || '' }
		/>
	);
}
