import {
	BlockPattern,
	__experimentalUseBlockPreview as useBlockPreview,
} from '@wordpress/block-editor';
import { Spinner } from '@wordpress/components';

import { ModalWithButtonTrigger } from './base-modal';
import { __ } from '../../../../utils/i18n';
import { useModalState } from '../../hooks/use-modal-state';
import { cloneBlockWithAttributes, usePatterns } from '../../hooks/use-patterns';

interface ItemPreviewProps {
	pattern: BlockPattern;
	onSelect: ( data: RemoteDataQueryInput ) => void;
	result: Record< string, string >;
}

function ItemPreview( props: ItemPreviewProps ) {
	const blocks = props.pattern.blocks.map( block =>
		cloneBlockWithAttributes( block, props.result )
	);
	const blockPreviewProps = useBlockPreview( { blocks, props: {} } );

	return (
		<li
			{ ...blockPreviewProps }
			onClick={ () => props.onSelect( props.result ) }
			onKeyDown={ () => props.onSelect( props.result ) }
			// eslint-disable-next-line jsx-a11y/no-noninteractive-element-to-interactive-role
			role="button"
			style={ { cursor: 'pointer', listStyle: 'none' } }
			tabIndex={ 0 }
		/>
	);
}

interface ItemListProps {
	blockName: string;
	loading: boolean;
	noResultsText: string;
	onSelect: ( data: RemoteDataQueryInput ) => void;
	placeholderText: string;
	results?: RemoteData[ 'results' ];
}

function ItemList( props: ItemListProps ) {
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

	return props.results.map( ( result, index ) => (
		<ItemPreview key={ index } onSelect={ props.onSelect } pattern={ pattern } result={ result } />
	) );
}

export interface ItemListModalProps {
	blockName: string;
	buttonText: string;
	loading: boolean;
	onOpen?: () => void;
	onSelect: ( data: RemoteDataQueryInput ) => void;
	results?: RemoteData[ 'results' ];
	searchControl?: JSX.Element;
	title: string;
}

export function ItemListModal( props: ItemListModalProps ) {
	const { close, isOpen, open } = useModalState( props.onOpen );

	function wrappedOnSelect( data: RemoteDataQueryInput ): void {
		props.onSelect( data );
		close();
	}

	return (
		<ModalWithButtonTrigger
			buttonText={ props.buttonText }
			isOpen={ isOpen }
			onClose={ close }
			onOpen={ open }
			title={ props.title }
		>
			<>
				{ props.searchControl }
				<ItemList
					blockName={ props.blockName }
					loading={ props.loading }
					noResultsText={ __( 'No items found' ) }
					onSelect={ wrappedOnSelect }
					placeholderText={ __( 'Select an item' ) }
					results={ props.results }
				/>
			</>
		</ModalWithButtonTrigger>
	);
}
