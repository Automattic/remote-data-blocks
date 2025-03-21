import { BlockEditorStoreSelectors, store as blockEditorStore } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';

import { cloneBlockForPreview } from '@/utils/block-binding';

import type { BlockInstance } from '@wordpress/blocks';

export function useGetInnerBlocks(
	blockName: string,
	clientId: string,
	remoteDataBlockName?: string
) {
	const { getBlocks } = useSelect< BlockEditorStoreSelectors >( blockEditorStore, [
		blockName,
		[ blockName, clientId ],
	] );

	return ( result: RemoteDataApiResult ): BlockInstance< RemoteDataInnerBlockAttributes >[] => {
		return getBlocks( clientId ).map( block =>
			cloneBlockForPreview( block, result, remoteDataBlockName ?? blockName )
		);
	};
}
