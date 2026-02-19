import { store as blockEditorStore } from '@wordpress/block-editor';
import { BlockConfiguration } from '@wordpress/blocks';
import { select } from '@wordpress/data';

import { REMOTE_DATA_CONTEXT_KEY } from '@/blocks/remote-data-container/config/constants';

/**
 * Get the list of blocks that support bindings from WordPress core.
 *
 * @returns Record of block names to their supported attributes
 */
function getSupportedBlockBindings(): Record< string, string[] > {
	try {
		// Get the block editor settings which contain the supported bindings
		const editorSettings = select( blockEditorStore )?.getSettings?.();
		// @ts-ignore - __experimentalBlockBindingsSupportedAttributes is not in types
		return editorSettings?.__experimentalBlockBindingsSupportedAttributes ?? {};
	} catch ( error ) {
		// If the store isn't available yet (e.g., during initial load), return empty
		return {};
	}
}

export function addUsesContext(
	settings: BlockConfiguration< RemoteDataInnerBlockAttributes >,
	name: string
) {
	// Check if this block supports bindings according to WordPress core
	const supportedBindings = getSupportedBlockBindings();
	const blockSupportsBindings = name in supportedBindings && supportedBindings[ name ].length > 0;

	if ( ! blockSupportsBindings ) {
		return settings;
	}

	const { usesContext = [] } = settings;

	if ( ! usesContext?.includes( REMOTE_DATA_CONTEXT_KEY ) ) {
		return {
			...settings,
			usesContext: [ ...usesContext, REMOTE_DATA_CONTEXT_KEY ],
		};
	}

	return settings;
}
