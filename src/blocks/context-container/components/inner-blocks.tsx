import { InnerBlocks as CoreInnerBlocks } from '@wordpress/block-editor';
import { BlockInstance } from '@wordpress/blocks';

import { LoopTemplate } from './loop-template';

interface InnerBlocksProps {
	blockConfig: BlockConfig;
	getInnerBlocks: (
		result: Record< string, string >
	) => BlockInstance< ContextInnerBlockAttributes >[];
	remoteData: RemoteData;
}

export function InnerBlocks( props: InnerBlocksProps ) {
	const {
		blockConfig: { loop },
		getInnerBlocks,
		remoteData,
	} = props;

	if ( loop ) {
		return <LoopTemplate getInnerBlocks={ getInnerBlocks } remoteData={ remoteData } />;
	}

	return <CoreInnerBlocks renderAppender={ CoreInnerBlocks.DefaultBlockAppender } />;
}
