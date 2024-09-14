import { Spinner } from '@wordpress/components';

import { ItemPreview } from '@/blocks/remote-data-container/components/item-list/item-preview';
import {
	cloneBlockWithAttributes,
	usePatterns,
} from '@/blocks/remote-data-container/hooks/use-patterns';
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
	const { getPatternsByBlockTypes } = usePatterns( props.blockName, '' );
	const [ pattern ] = getPatternsByBlockTypes( props.blockName );

	if ( props.loading || ! pattern ) {
		return <Spinner />;
	}

	if ( ! props.results ) {
		return <p>{ __( props.placeholderText ) }</p>;
	}

	if ( props.results.length === 0 ) {
		return <p>{ __( props.noResultsText ) }</p>;
	}

	return props.results.map( ( result, index ) => {
		const blocks =
			pattern?.blocks.map( block => cloneBlockWithAttributes( block, result, props.blockName ) ) ??
			[];

		return (
			<ItemPreview key={ index } blocks={ blocks } onSelect={ () => props.onSelect( result ) } />
		);
	} );
}
