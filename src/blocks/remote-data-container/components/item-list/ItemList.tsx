import { Spinner } from '@wordpress/components';
import { DataViews, View } from '@wordpress/dataviews';
import { useState } from '@wordpress/element';

import { ItemPreview } from '@/blocks/remote-data-container/components/item-list/ItemPreview';
import {
	cloneBlockWithAttributes,
	usePatterns,
} from '@/blocks/remote-data-container/hooks/usePatterns';
import { __ } from '@/utils/i18n';
import { getBlockConfig } from '@/utils/localized-block-data';

interface ItemListProps {
	blockName: string;
	loading: boolean;
	noResultsText: string;
	onSelect: ( data: RemoteDataQueryInput ) => void;
	placeholderText: string;
	results?: RemoteData[ 'results' ];
}

const DEFAULT_VIEW = {
	type: 'table' as const,
	search: '',
	page: 1,
	perPage: 10,
	layout: {},
	filters: [],
};

type RenderItemFunction = ( { item }: { item: Record< string, string > } ) => JSX.Element | null;
interface RemoteDataField {
	label: string;
	id: string;
	render: RenderItemFunction;
	enableHiding: boolean;
	enableSorting: boolean;
}

export function ItemList( props: ItemListProps ) {
	const [ selection, setSelection ] = useState< string[] >();
	const [ view, setView ] = useState< View >( {
		...DEFAULT_VIEW,
		fields: [ 'id', 'image_url', 'price', 'title' ],
	} );
	const { defaultPattern: pattern } = usePatterns( props.blockName );

	if ( props.loading || ! pattern ) {
		return <Spinner />;
	}

	if ( ! props.results ) {
		return <p>{ __( props.placeholderText ) }</p>;
	}

	if ( props.results.length === 0 ) {
		return <p>{ __( props.noResultsText ) }</p>;
	}

	const defaultLayouts = {
		table: {
			layout: {
				primaryField: 'id',
				styles: {
					image_url: {
						width: 50,
					},
					title: {
						maxWidth: 400,
					},
					price: {
						width: 50,
					},
					id: {
						maxWidth: 200,
					},
				},
			},
		},
		grid: {
			layout: {
				mediaField: 'image_url',
				primaryField: 'id',
			},
		},
		list: {
			layout: {
				mediaField: 'image_url',
				primaryField: 'id',
			},
		},
	};

	// todo: memoize
	const blockConfig = getBlockConfig( props.blockName );
	const mappings = blockConfig?.outputSchema?.mappings;

	if ( mappings ) {
		const fields = Object.keys( mappings )
			.map( key => {
				const mapping = mappings[ key ];
				if ( ! ( mapping?.name && mapping.type ) ) {
					return undefined;
				}
				let renderFn: RenderItemFunction = () => null;

				switch ( mapping.type ) {
					case 'image_url':
						renderFn = ( { item } ) => {
							return <img src={ item.image_url } alt="" style={ { width: '100%' } } />;
						};
						break;
					case 'id':
					case 'price':
					case 'string':
					default:
						renderFn = ( { item } ) => {
							return <span>{ item[ key ] }</span>;
						};
						break;
				}

				return {
					label: mapping.name,
					id: key,
					render: renderFn,
					enableHiding: false,
					enableSorting: 'image_url' !== mapping.type,
				};
			} )
			.filter< RemoteDataField >(
				// eslint-disable-next-line @typescript-eslint/no-unsafe-argument, @typescript-eslint/no-explicit-any
				Boolean as any
			);

		return (
			<DataViews
				getItemId={ item => item.id?.toString() || '' }
				paginationInfo={ {
					totalItems: props.results.length,
					totalPages: Math.ceil( props.results.length / DEFAULT_VIEW.perPage ),
				} }
				data={ props.results }
				fields={ fields }
				view={ view }
				onChangeView={ setView }
				onChangeSelection={ newSelection => {
					console.log( { newSelection } );
					setSelection( newSelection );
				} }
				defaultLayouts={ defaultLayouts }
				selection={ selection }
			/>
		);
	}

	return (
		<ul>
			{ props.results.map( ( result, index ) => {
				const blocks =
					pattern?.blocks.map( block =>
						cloneBlockWithAttributes( block, result, props.blockName )
					) ?? [];

				return (
					<ItemPreview
						key={ index }
						blocks={ blocks }
						onSelect={ () => props.onSelect( result ) }
					/>
				);
			} ) }
		</ul>
	);
}
