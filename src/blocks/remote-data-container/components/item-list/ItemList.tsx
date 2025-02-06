import { Button } from '@wordpress/components';
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
	onSelectField?: ( data: FieldSelection, fieldValue: string ) => void;
	remoteData?: RemoteData;
	searchTerms: string;
	setSearchTerms: ( newValue: string ) => void;
}

const createFieldSelection = (
	field: string,
	item: RemoteDataResult,
	blockName: string,
	remoteData: RemoteData
): FieldSelection => ( {
	action: 'add_field_shortcode',
	remoteData: {
		...remoteData,
		blockName,
		queryInput: {
			...item,
			field: {
				field,
				value: item[ field ] as string,
			},
		},
		resultId: item.id?.toString() ?? '',
		results: [ item ],
	},
	selectedField: field,
	selectionPath: 'select_new_tab',
	type: 'field',
} );

export function ItemList( props: ItemListProps ) {
	const {
		availableBindings,
		blockName,
		loading,
		onSelect,
		onSelectField,
		remoteData,
		searchTerms,
		setSearchTerms,
	} = props;
	const results = remoteData?.results ?? [];
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

		const renderField = ( field: string, item: RemoteDataResult ) => {
			if ( field === media ) {
				return <img alt={ ( item.image_alt as string ) ?? '' } src={ item[ field ] as string } />;
			}

			if ( onSelectField && remoteData ) {
				const queryInput: RemoteDataQueryInput = {
					...item,
					field: {
						field,
						value: item[ field ] as string,
					},
				};

				return (
					<Button
						onClick={ () => {
							onSelectField(
								createFieldSelection( field, item, blockName, remoteData ),
								item[ field ] as string
							);
							onSelect( queryInput );
						} }
						variant="link"
					>
						{ item[ field ] as string }
					</Button>
				);
			}

			return item[ field ] as string;
		};

		const fieldObject = getFields.map( field => ( {
			id: field,
			label: availableBindings[ field ]?.name ?? field,
			enableGlobalSearch: true,
			getValue: ( { item }: { item: RemoteDataResult } ) => item[ field ] as string,
			render: ( { item }: { item: RemoteDataResult } ) => renderField( field, item ),
			enableSorting: field !== media,
		} ) );

		return { fields: fieldObject, tableFields: getFields, titleField: title, mediaField: media };
	}, [ availableBindings, data, onSelectField, remoteData ] );

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

	// Hide actions for field shortcode selection
	const actions = ! onSelectField
		? [
				{
					id: 'choose',
					icon: <>{ __( 'Choose' ) }</>,
					isPrimary: true,
					label: '',
					callback: ( items: RemoteDataResult[] ) => {
						items.map( item => onSelect( item ) );
					},
				},
		  ]
		: [];

	return (
		<DataViews
			actions={ actions }
			data={ filteredData }
			defaultLayouts={ defaultLayouts }
			fields={ fields }
			getItemId={ ( item: { id?: string } ) => item.id || '' }
			isLoading={ loading || ! pattern || ! results }
			isItemClickable={ () => true }
			onClickItem={ item => onSelect( item ) }
			onChangeView={ setView }
			paginationInfo={ paginationInfo }
			view={ view }
		/>
	);
}
