import {
	BlockEditorStoreSelectors,
	store as blockEditorStore,
	useBlockEditContext,
} from '@wordpress/block-editor';
import { useDispatch, useSelect } from '@wordpress/data';
import { Fragment, useState } from '@wordpress/element';

import { ItemPreview } from '@/blocks/remote-data-template/components/item-preview/ItemPreview';
import { LoopTemplateInnerBlocks } from '@/blocks/remote-data-template/components/loop-template/LoopTemplateInnerBlocks';
import { STORE_NAME as remoteDataBlocksStore } from '@/config/constants';

import type { ActionCreators } from '@/store';
import type { BlockInstance } from '@wordpress/blocks';

interface LoopTemplateProps {
	getInnerBlocks: (
		result: RemoteDataApiResult,
		index: number
	) => BlockInstance< RemoteDataInnerBlockAttributes >[];
	remoteData: RemoteData;
}

export function LoopTemplate( props: LoopTemplateProps ) {
	const { getInnerBlocks, remoteData } = props;

	// Use local state instead of selecting the preview index from the store so
	// that re-renders are limited to this component only.
	const [ activeBlockIndex, setActiveBlockIndex ] = useState< number >( 0 );
	const { setPreviewIndex } = useDispatch< ActionCreators >( remoteDataBlocksStore, [] );

	// Hammer approach, forces re-render of the whole loop when user input is detected.
	const { clientId } = useBlockEditContext();
	useSelect< BlockEditorStoreSelectors, BlockInstance[] >(
		select => select( blockEditorStore ).getBlocksByClientId( clientId ),
		[ clientId ]
	);

	function onSelect( index: number ): void {
		setActiveBlockIndex( index );
		setPreviewIndex( remoteData.resultId, index );
	}

	// To avoid flicker when switching active block contexts, a preview is rendered
	// for each block context, but the preview for the active block context is hidden.
	// This ensures that when it is displayed again, the cached rendering of the
	// block preview is used, instead of having to re-render the preview from scratch.

	return (
		<ul className="remote-data-blocks-loop-template">
			{ remoteData.results.map( ( result, index ) => {
				const isActive = index === activeBlockIndex;
				return (
					<Fragment key={ index }>
						<LoopTemplateInnerBlocks isActive={ isActive } />
						<ItemPreview
							blocks={ getInnerBlocks( result, index ) }
							isHidden={ isActive }
							onSelect={ () => onSelect( index ) }
						/>
					</Fragment>
				);
			} ) }
		</ul>
	);
}
