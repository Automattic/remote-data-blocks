import { useInstanceId } from '@wordpress/compose';
// eslint-disable-next-line import/no-extraneous-dependencies
import { DataViews, filterSortAndPaginate, View } from '@wordpress/dataviews';
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
}

export function ItemList( props: ItemListProps ) {
	const { defaultPattern: pattern } = usePatterns( props.blockName );

	const instanceId = useInstanceId( ItemList, props.blockName );

	// ensure each result has an 'id' key
	const data = useMemo( () => {
		return ( props.results ?? [] ).map( ( item: Record< string, unknown > ) =>
			item.id
				? item
				: {
						...item,
						id: Object.keys( item ).find( key => /(^|_)(id)$/i.test( key ) ) // Regex to match 'id' or part of '_id'
							? item[ Object.keys( item ).find( key => /(^|_)(id)$/i.test( key ) ) as string ]
							: instanceId,
				  }
		) as RemoteData[ 'results' ];
	}, [ props.results ] );

	const [ view, setView ] = useState< View >( {
		type: 'table',
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
					props.results?.flatMap( Object.keys ).filter( key => ! /(^|_)(id)$/i.test( key ) ) // Filters out keys containing 'id' or similar patterns
				)
			),

		[ props.results ]
	);

	const fields = tableFields.map( field => {
		const isImageField =
			field.toLowerCase().includes( 'url' ) ||
			field.toLowerCase().includes( 'image' ) ||
			props.results?.some(
				item => typeof item[ field ] === 'string' && item[ field ].startsWith( 'data:image/' )
			);
		return {
			id: field,
			label: field,
			enableGlobalSearch: true,
			render: isImageField
				? ( { item }: { item: Record< string, unknown > } ) => {
						return (
							<img
								// temporary until we pull in more data
								alt=""
								src={ item[ field ] as string }
								style={ { maxWidth: '250px' } }
							/>
						);
				  }
				: undefined,
			enableSorting: ! isImageField,
		};
	} );

	const primaryField = 'id';
	const mediaField =
		tableFields.find(
			field => field.toLowerCase().includes( 'url' ) || field.toLowerCase().includes( 'image' )
		) || '';

	const defaultLayouts = {
		table: {
			layout: {
				primaryField,
			},
		},
		list: {
			layout: {
				primaryField,
				mediaField,
			},
		},
		grid: {
			layout: {
				primaryField,
				mediaField,
			},
		},
	};

	useEffect( () => {
		if ( tableFields.length > 0 ) {
			setView( prevView => ( {
				...prevView,

				fields: tableFields,
			} ) );
		}
	}, [ tableFields ] );

	// filter, sort and paginate data
	const { data: results, paginationInfo } = useMemo( () => {
		return filterSortAndPaginate( data ?? [], view, fields );
	}, [ view ] );

	if ( ! props.results ) {
		return <p>{ __( props.placeholderText ) }</p>;
	}

	if ( props.results.length === 0 ) {
		return <p>{ __( props.noResultsText ) }</p>;
	}

	const actions = [
		{
			id: 'select',
			label: __( 'Select item' ),
			// isPrimary: true,
			// supportsBulk: true,
			callback: ( items: unknown[] ) => {
				( items as RemoteData[ 'results' ] ).map( item => props.onSelect( item ) );
			},
		},
	];

	return (
		<DataViews
			actions={ actions }
			data={ results }
			fields={ fields }
			view={ view }
			onChangeView={ setView }
			paginationInfo={ paginationInfo }
			defaultLayouts={ defaultLayouts }
			isLoading={ props.loading || ! pattern }
			getItemId={ ( item: { id?: string } ) => item.id || '' }
		/>
	);
}
