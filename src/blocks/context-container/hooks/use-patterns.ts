import {
	BlockEditorStoreActions,
	BlockEditorStoreSelectors,
	BlockPattern,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { BlockInstance, cloneBlock } from '@wordpress/blocks';
import { useDispatch, useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';

import { getBoundAttributeEntries, getMismatchedAttributes } from '../../../utils/block-binding';

function cloneBlockWithAttributes(
	block: BlockInstance,
	attributes: Record< string, string >
): BlockInstance {
	const mismatchedAttributes = getMismatchedAttributes( block.attributes, [ attributes ] );
	const newInnerBlocks = block.innerBlocks?.map( innerBlock =>
		cloneBlockWithAttributes( innerBlock, attributes )
	);

	return cloneBlock( block, mismatchedAttributes, newInnerBlocks );
}

export function usePatterns( blockName: string, rootClientId: string ) {
	const { replaceInnerBlocks } = useDispatch< BlockEditorStoreActions >( blockEditorStore );
	const { getBlocks, getPatternsByBlockTypes } = useSelect< BlockEditorStoreSelectors >(
		blockEditorStore,
		[ blockName, rootClientId ]
	);
	const [ showPatternSelection, setShowPatternSelection ] = useState< boolean >( false );

	return {
		getInnerBlocks: (
			result: Record< string, string >
		): BlockInstance< ContextInnerBlockAttributes >[] => {
			return getBlocks< ContextInnerBlockAttributes >( rootClientId ).map( block =>
				cloneBlockWithAttributes( block, result )
			);
		},
		getSupportedPatterns: ( result?: Record< string, string > ): BlockPattern[] => {
			const supportedPatterns = getPatternsByBlockTypes( blockName, rootClientId );

			// If no result is provided, return the supported patterns as is.
			if ( ! result ) {
				return supportedPatterns;
			}

			// Clone the pattern blocks and inject the provided result data so that
			// it can be previewed.
			return supportedPatterns.map( pattern => ( {
				...pattern,
				blocks: pattern.blocks.map( block => cloneBlockWithAttributes( block, result ) ),
			} ) );
		},
		insertPatternBlocks: ( pattern: BlockPattern ): void => {
			// Clone the pattern blocks with bindings to allow the user to make changes.
			// We always insert a single representation of the pattern, even if it is a
			// collection. The InnerBlocksLoop component will handle rendering the rest
			// of the collection.
			const patternBlocks =
				pattern.blocks.map( block => {
					const boundAttributes = getBoundAttributeEntries( block.attributes );

					if ( ! boundAttributes.length ) {
						return block;
					}

					return cloneBlock( block );
				} ) ?? [];

			replaceInnerBlocks( rootClientId, patternBlocks ).catch( () => {} );
			setShowPatternSelection( false );
		},
		removeInnerBlocks: (): void => {
			replaceInnerBlocks( rootClientId, [] ).catch( () => {} );
		},
		setShowPatternSelection,
		showPatternSelection,
	};
}
