import { BlockContextProvider } from '@wordpress/block-editor';
import { Spinner } from '@wordpress/components';

import { REMOTE_DATA_CONTEXT_KEY } from '../../config/constants';
import { ItemPreview } from '@/blocks/remote-data-container/components/item-list/ItemPreview';
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

	if ( props.loading || ! pattern ) {
		return <Spinner />;
	}

	if ( ! props.results ) {
		return <p>{ __( props.placeholderText ) }</p>;
	}

	if ( props.results.length === 0 ) {
		return <p>{ __( props.noResultsText ) }</p>;
	}

	const blocks = pattern?.blocks ?? [];

	return (
		<ul>
			{ props.results.map( ( result, index ) => {
				const context = {
					[ REMOTE_DATA_CONTEXT_KEY ]: {
						results: [ result ],
						blockName: props.blockName,
					},
				};

				return (
					<BlockContextProvider value={ context } key={ index }>
						<ItemPreview blocks={ blocks } onSelect={ () => props.onSelect( result ) } />
					</BlockContextProvider>
				);
			} ) }
		</ul>
	);
}
