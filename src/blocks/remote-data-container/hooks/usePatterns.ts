import {
	BlockEditorStoreActions,
	BlockEditorStoreSelectors,
	BlockPattern,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { BlockInstance, cloneBlock, createBlock } from '@wordpress/blocks';
import { useDispatch, useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';

import {
	cloneBlockForPatternPreview,
	getBoundAttributeEntries,
	hasBlockBinding,
	isSyncedPattern,
} from '@/utils/block-binding';
import { getBlockConfig } from '@/utils/localized-block-data';

export function usePatterns(
	remoteDataBlockName: string,
	rootClientId: string = '',
	addPaginationBlock = false
) {
	const { patterns } = getBlockConfig( remoteDataBlockName ) ?? {};
	const { replaceInnerBlocks } = useDispatch< BlockEditorStoreActions >( blockEditorStore );
	const { getPatternsByBlockTypes, allowedPatterns } = useSelect<
		BlockEditorStoreSelectors,
		Pick< BlockEditorStoreSelectors, 'getBlocks' | 'getPatternsByBlockTypes' > & {
			allowedPatterns: BlockPattern[];
		}
	>(
		select => {
			const store = select( blockEditorStore );
			return {
				getBlocks: store.getBlocks,
				getPatternsByBlockTypes: store.getPatternsByBlockTypes,
				allowedPatterns: store.__experimentalGetAllowedPatterns( rootClientId ) ?? [],
			};
		},
		[ remoteDataBlockName, rootClientId ]
	);
	const [ showPatternSelection, setShowPatternSelection ] = useState< boolean >( false );

	// Extract patterns with defined roles
	const patternsByBlockTypes = getPatternsByBlockTypes( remoteDataBlockName );
	const innerBlocksPattern = patternsByBlockTypes.find(
		( { name } ) => name === patterns?.inner_blocks
	);

	// Filter allowed patterns for those that have a relevant binding.
	const supportedPatterns = allowedPatterns.filter(
		pattern =>
			pattern?.blockTypes?.includes( remoteDataBlockName ) ||
			pattern.blocks.some( block => hasBlockBinding( block, remoteDataBlockName ) )
	);

	function getInnerBlocks( pattern: BlockPattern ): BlockInstance[] {
		// If the pattern is a synced pattern, insert it directly.
		if ( isSyncedPattern( pattern ) ) {
			const syncedPattern = createBlock( 'core/block', { ref: pattern.id } );
			const loopTemplate = createBlock( 'remote-data-blocks/template', {}, [ syncedPattern ] );
			return [ loopTemplate ];
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
		const loopTemplate = createBlock( 'remote-data-blocks/template', {}, patternBlocks );

		return [ loopTemplate ];
	}

	function insertPatternBlocks( pattern: BlockPattern ): void {
		const innerBlocks = getInnerBlocks( pattern );

		if ( addPaginationBlock ) {
			innerBlocks.push( createBlock( 'remote-data-blocks/pagination' ) );
		}

		// Add the no-results block with the empty mode.
		innerBlocks.push( createBlock( 'remote-data-blocks/no-results', { mode: 'empty' } ) );

		replaceInnerBlocks( rootClientId, innerBlocks ).catch( () => {} );
	}

	function onReadyForPatternSelection(): void {
		if ( innerBlocksPattern ) {
			insertPatternBlocks( innerBlocksPattern );
			return;
		}

		setShowPatternSelection( true );
	}

	function onSelectPattern( pattern: BlockPattern ): void {
		const realPattern = supportedPatterns.find( p => p.name === pattern.name );
		insertPatternBlocks( realPattern ?? pattern );
		setShowPatternSelection( false );
	}

	function resetPatternSelection(): void {
		replaceInnerBlocks( rootClientId, [] ).catch( () => {} );
		setShowPatternSelection( false );
	}

	return {
		getSupportedPatterns: ( result?: RemoteDataApiResult ): BlockPattern[] => {
			// If no result is provided, return the supported patterns as is.
			if ( ! result ) {
				return supportedPatterns;
			}

			// Clone the pattern blocks and inject the provided result data so that
			// it can be previewed.
			return supportedPatterns.map( pattern => ( {
				...pattern,
				blocks: pattern.blocks.map( block =>
					cloneBlockForPatternPreview( block, result, remoteDataBlockName )
				),
			} ) );
		},
		onReadyForPatternSelection,
		onSelectPattern,
		resetPatternSelection,
		showPatternSelection,
	};
}
