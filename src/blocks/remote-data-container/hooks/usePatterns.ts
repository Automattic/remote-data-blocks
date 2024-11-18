import {
	BlockEditorStoreActions,
	BlockEditorStoreSelectors,
	BlockPattern,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { BlockInstance, cloneBlock, createBlock } from '@wordpress/blocks';
import { useDispatch, useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';

import { getBoundAttributeEntries, hasBlockBinding, isSyncedPattern } from '@/utils/block-binding';
import { getBlockConfig } from '@/utils/localized-block-data';

export function usePatterns( remoteDataBlockName: string, rootClientId: string = '' ) {
	const { patterns } = getBlockConfig( remoteDataBlockName ) ?? {};
	const { replaceInnerBlocks } = useDispatch< BlockEditorStoreActions >( blockEditorStore );
	const { getBlocks, getPatternsByBlockTypes, __experimentalGetAllowedPatterns } =
		useSelect< BlockEditorStoreSelectors >( blockEditorStore, [
			remoteDataBlockName,
			[ remoteDataBlockName, rootClientId ],
		] );
	const [ showPatternSelection, setShowPatternSelection ] = useState< boolean >( false );

	// Extract patterns with defined roles.
	const patternsByBlockTypes = getPatternsByBlockTypes( remoteDataBlockName, rootClientId );
	const defaultPattern = patternsByBlockTypes.find( ( { name } ) => name === patterns?.default );
	const innerBlocksPattern = patternsByBlockTypes.find(
		( { name } ) => name === patterns?.inner_blocks
	);

	const returnValue = {
		defaultPattern,
		getInnerBlocks: (): BlockInstance< RemoteDataInnerBlockAttributes >[] => {
			return getBlocks< RemoteDataInnerBlockAttributes >( rootClientId );
		},
		getSupportedPatterns: (): BlockPattern[] => {
			const supportedPatterns = __experimentalGetAllowedPatterns( rootClientId ).filter(
				pattern =>
					pattern?.blockTypes?.includes( remoteDataBlockName ) ||
					pattern.blocks.some( block => hasBlockBinding( block, remoteDataBlockName ) )
			);

			return supportedPatterns;
		},
		insertPatternBlocks: ( pattern: BlockPattern ): void => {
			setShowPatternSelection( false );

			// If the pattern is a synced pattern, insert it directly.
			if ( isSyncedPattern( pattern ) ) {
				const syncedPattern = createBlock( 'core/block', { ref: pattern.id } );
				replaceInnerBlocks( rootClientId, [ syncedPattern ] ).catch( () => {} );
				return;
			}

			// Clone the pattern blocks with bindings to allow the user to make changes.
			// We always insert a single representation of the pattern, even if it is a
			// collection. The InnerBlocksLoop component will handle rendering the rest
			// of the collection.
			const patternBlocks =
				pattern.blocks.map( block => {
					const boundAttributes = getBoundAttributeEntries( block.attributes, remoteDataBlockName );

					if ( ! boundAttributes.length ) {
						return block;
					}

					return cloneBlock( block );
				} ) ?? [];

			replaceInnerBlocks( rootClientId, patternBlocks ).catch( () => {} );
		},
		markReadyForInsertion: (): void => {
			if ( innerBlocksPattern ) {
				returnValue.insertPatternBlocks( innerBlocksPattern );
				return;
			}

			setShowPatternSelection( true );
		},
		resetReadyForInsertion: (): void => {
			replaceInnerBlocks( rootClientId, [] ).catch( () => {} );
			setShowPatternSelection( false );
		},
		showPatternSelection,
	};

	return returnValue;
}
