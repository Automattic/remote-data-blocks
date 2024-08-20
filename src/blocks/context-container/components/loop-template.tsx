import {
	BlockEditorStoreSelectors,
	store as blockEditorStore,
	useBlockEditContext,
} from '@wordpress/block-editor';
import { BlockInstance } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { LoopTemplateInnerBlocks } from './loop-template-inner-blocks';
import { MemoizedLoopTemplatePreview } from './loop-template-preview';
import { LoopIndexContext } from '../context/loop-index-context';

interface LoopTemplateProps {
	getInnerBlocks: (
		result: Record< string, string >
	) => BlockInstance< ContextInnerBlockAttributes >[];
	remoteData: RemoteData;
}

export function LoopTemplate( props: LoopTemplateProps ) {
	const [ activeBlockIndex, setActiveBlockIndex ] = useState< number >( 0 );
	const { getInnerBlocks, remoteData } = props;

	// Hammer approach, forces re-render of the whole loop when user input is detected.
	const { clientId } = useBlockEditContext();
	useSelect< BlockEditorStoreSelectors, BlockInstance[] >(
		select => select( blockEditorStore ).getBlocksByClientId( clientId ),
		[ clientId ]
	);

	if ( ! remoteData.results.length ) {
		return <p>{ __( 'No results found.' ) }</p>;
	}

	// To avoid flicker when switching active block contexts, a preview is rendered
	// for each block context, but the preview for the active block context is hidden.
	// This ensures that when it is displayed again, the cached rendering of the
	// block preview is used, instead of having to re-render the preview from scratch.
	return (
		<ul>
			{ remoteData.results.map( ( result, index ) => {
				const isActive = index === activeBlockIndex;
				return (
					<LoopIndexContext.Provider key={ `template-${ index }` } value={ { index } }>
						<LoopTemplateInnerBlocks isActive={ isActive } />
						<MemoizedLoopTemplatePreview
							blocks={ getInnerBlocks( result ) }
							isHidden={ isActive }
							onActive={ () => setActiveBlockIndex( index ) }
						/>
					</LoopIndexContext.Provider>
				);
			} ) }
		</ul>
	);
}
