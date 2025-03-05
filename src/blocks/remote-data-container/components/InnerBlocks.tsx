import { InnerBlocks as CoreInnerBlocks } from '@wordpress/block-editor';
import { BlockInstance } from '@wordpress/blocks';

import { LoopTemplate } from '@/blocks/remote-data-container/components/loop-template/LoopTemplate';

interface InnerBlocksProps {
	blockConfig: BlockConfig;
	getInnerBlocks: ( result: RemoteDataResult ) => BlockInstance< RemoteDataInnerBlockAttributes >[];
	remoteData: RemoteData;
}

export function InnerBlocks( props: InnerBlocksProps ) {
	const {
		blockConfig: { loop, selectors },
		getInnerBlocks,
		remoteData,
	} = props;

	// Use loop template for both loop blocks and collections
	if (
		loop ||
		remoteData.results.length > 1 ||
		selectors.some( selector => selector.type === 'collection' )
	) {
		return <LoopTemplate getInnerBlocks={ getInnerBlocks } remoteData={ remoteData } />;
	}

	return <CoreInnerBlocks renderAppender={ CoreInnerBlocks.DefaultBlockAppender } />;
}
