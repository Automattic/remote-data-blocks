import { BlockEditorStoreSelectors, store as blockEditorStore } from '@wordpress/block-editor';
import { BlockConfiguration, BlockEditProps } from '@wordpress/blocks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { useSelect } from '@wordpress/data';

import { useRemoteDataContext } from '@/blocks/remote-data-container/hooks/useRemoteDataContext';
import {
	PATTERN_OVERRIDES_BINDING_SOURCE,
	PATTERN_OVERRIDES_CONTEXT_KEY,
} from '@/config/constants';
import { getBlockAvailableBindings } from '@/utils/localized-block-data';

export const withBlockBinding = createHigherOrderComponent( BlockEdit => {
	return ( props: BlockEditProps< RemoteDataInnerBlockAttributes > ) => {
		const { attributes, context } = props;
		const { remoteData } = useRemoteDataContext( context );
		const availableBindings = getBlockAvailableBindings( remoteData?.blockName ?? '' );
		const hasAvailableBindings = Boolean( Object.keys( availableBindings ).length );
		const { hasMultiSelection } = useSelect< BlockEditorStoreSelectors >( blockEditorStore );

		// If the block does not have a remote data context, render it as usual.
		if ( ! remoteData || ! hasAvailableBindings ) {
			return <BlockEdit { ...props } />;
		}

		// Synced pattern overrides are provided via context and the value can be:
		//
		// - undefined (block is not in a synced pattern)
		// - an empty array (block is in a synced pattern, but no overrides are applied)
		// - an object defining the applied overrides
		//
		// This gives no indication of whether overrides are enabled or not. For
		// that, we need to check the block's metadata bindings for the pattern
		// overrides binding source.
		//
		// This seems likely to change, so the code here may need maintenance. For
		// our purposes, though, we just want to know whether the block is in a
		// synced pattern and whether overrides are enabled. Trying to update
		// a synced block without overrides enabled is useless and can cause issues.

		const patternOverrides = context[ PATTERN_OVERRIDES_CONTEXT_KEY ] as string[] | undefined;
		const isInSyncedPattern = Boolean( patternOverrides );
		const hasEnabledOverrides = Object.values( attributes.metadata?.bindings ?? {} ).some(
			binding => binding.source === PATTERN_OVERRIDES_BINDING_SOURCE
		);

		// If multiple blocks are being selected, render it as usual.
		if ( hasMultiSelection() ) {
			return <BlockEdit { ...props } attributes={ attributes } />;
		}

		// If the block is not writable, render it as usual.
		if ( isInSyncedPattern && ! hasEnabledOverrides ) {
			return <BlockEdit { ...props } attributes={ attributes } />;
		}

		return <BlockEdit { ...props } />;
	};
}, 'withBlockBinding' );

/**
 * Shim for the block binding HOC to be used with the `blocks.registerBlockType` filter.
 */
export function withBlockBindingShim(
	settings: BlockConfiguration< RemoteDataInnerBlockAttributes >
): BlockConfiguration< RemoteDataInnerBlockAttributes > {
	return {
		...settings,
		edit: withBlockBinding( settings.edit ?? ( () => null ) ),
	};
}
