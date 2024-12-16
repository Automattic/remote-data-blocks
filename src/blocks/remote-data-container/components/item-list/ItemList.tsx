import { useInstanceId } from '@wordpress/compose';
import { DataViews, filterSortAndPaginate, Operator, View } from '@wordpress/dataviews/wp';
import { useEffect, useMemo, useState } from '@wordpress/element';

import { usePatterns } from '@/blocks/remote-data-container/hooks/usePatterns';
import { __ } from '@/utils/i18n';

interface ItemListProps {
	blockName: string;
	loading: boolean;
	noResultsText: string;
	onSelect: ( data: RemoteDataQueryInput ) => void;
	placeholderText: string;
	results?: RemoteData[ 'results' ];
	searchTerms: string;
	setSearchTerms: ( newValue: string ) => void;
}

export function ItemList( props: ItemListProps ) {
	const {
		blockName,
		loading,
		noResultsText,
		onSelect,
		placeholderText,
		results,
		searchTerms,
		setSearchTerms,
	} = props;
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

	const [ view, setView ] = useState< View >( {
		type: 'table' as const,
		perPage: 10,
		page: 1,
		search: '',
		fields: [],
		filters: [],
		layout: {},
	} );

	// get fields from results data to use as columns
	const tableFields = useMemo(
		() =>
			Array.from(
				new Set(
					results?.flatMap( Object.keys ).filter( key => ! /(^|_)(id)$/i.test( key ) ) // Filters out keys containing 'id' or similar patterns
				)
			),

		[ results ]
	);

	const mediaField =
		tableFields.find(
			field => field.toLowerCase().includes( 'url' ) || field.toLowerCase().includes( 'image' )
		) || '';

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
					? ( { item }: { item: Record< string, unknown > } ) => {
							return (
								<img
									// temporary until we pull in more data
									alt=""
									src={ item[ field ] as string }
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
		table: {
			showMedia: false,
		},
		grid: {
			mediaField,
		},
		list: {
			mediaField,
		},
	};

	useEffect( () => {
		if ( tableFields.length > 0 ) {
			setView( prevView => ( {
				...prevView,
				fields: tableFields.filter( field => field !== mediaField ),
			} ) );
		}
	}, [ tableFields ] );

	useEffect( () => {
		if ( view.search !== searchTerms ) {
			setSearchTerms( view.search ?? '' );
		}
	}, [ view, searchTerms ] );

	// filter, sort and paginate data
	const { data: filteredData, paginationInfo } = useMemo( () => {
		return filterSortAndPaginate( data ?? [], view, fields );
	}, [ data, view ] );

	if ( ! results ) {
		return <p>{ __( placeholderText ) }</p>;
	}

	if ( results.length === 0 ) {
		return <p>{ __( noResultsText ) }</p>;
	}

	const actions = [
		{
			id: 'select',
			label: __( 'Select item' ),
			callback: ( items: unknown[] ) => {
				( items as RemoteData[ 'results' ] ).map( item => onSelect( item ) );
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
