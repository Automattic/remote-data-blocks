import {
	BlockEditorStoreSelectors,
	InspectorControls,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { BlockConfiguration, BlockEditProps } from '@wordpress/blocks';
import { PanelBody } from '@wordpress/components';
import { createHigherOrderComponent } from '@wordpress/compose';
import { useSelect } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';

import { BlockBindingControls } from '@/blocks/remote-data-container/components/BlockBindingControls';
import { useRemoteDataContext } from '@/blocks/remote-data-container/hooks/useRemoteDataContext';
import {
	BLOCK_BINDING_SOURCE,
	PATTERN_OVERRIDES_BINDING_SOURCE,
	PATTERN_OVERRIDES_CONTEXT_KEY,
} from '@/config/constants';
import { getBoundBlockClassName, getMismatchedAttributes } from '@/utils/block-binding';
import { getBlockAvailableBindings, getBlockTitle } from '@/utils/localized-block-data';

interface BoundBlockEditProps {
	attributes: RemoteDataInnerBlockAttributes;
	availableBindings: AvailableBindings;
	blockName: string;
	children: JSX.Element;
	remoteDataName: string;
	remoteDataTitle: string;
	setAttributes: ( attributes: RemoteDataInnerBlockAttributes ) => void;
}

// This prop is provided by the `withPreviewIndex` filter, which is bundled with
// the Remote Data Template block.
interface BlockEditWithPreviewIndex {
	previewIndex?: number;
}

function BoundBlockEdit( props: BoundBlockEditProps ) {
	const {
		attributes,
		availableBindings,
		blockName,
		remoteDataName,
		remoteDataTitle,
		setAttributes,
	} = props;
	const existingBindings = attributes.metadata?.bindings ?? {};

	function removeBinding( target: string ) {
		const { [ target ]: _remove, ...newBindings } = existingBindings;
		setAttributes( {
			metadata: {
				...attributes.metadata,
				bindings: newBindings,
				name: undefined,
			},
		} );
	}

	function updateBinding( target: string, args: Omit< RemoteDataBlockBindingArgs, 'block' > ) {
		setAttributes( {
			className: getBoundBlockClassName( attributes, remoteDataName ),
			metadata: {
				...attributes.metadata,
				bindings: {
					...attributes.metadata?.bindings,
					[ target ]: {
						source: BLOCK_BINDING_SOURCE,
						args: {
							...args,
							block: remoteDataName, // Remote Data Block name
						},
					},
				},
				name: availableBindings[ args.field ]?.name, // Changes block name in list view.
			},
		} );
	}

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Remote Data', 'remote-data-blocks' ) }>
					<p className="rdb-block-helper-text">
						{ sprintf( __( 'Connected to %s', 'remote-data-blocks' ), remoteDataTitle ) }
					</p>
					<BlockBindingControls
						attributes={ attributes }
						availableBindings={ availableBindings }
						blockName={ blockName }
						remoteDataName={ remoteDataName }
						removeBinding={ removeBinding }
						updateBinding={ updateBinding }
					/>
				</PanelBody>
			</InspectorControls>
			{ props.children }
		</>
	);
}

export const withBlockBinding = createHigherOrderComponent( BlockEdit => {
	return (
		props: BlockEditProps< RemoteDataInnerBlockAttributes > & BlockEditWithPreviewIndex
	) => {
		const { attributes, context, name, previewIndex: index = 0, setAttributes } = props;
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

		// If the block has a binding and the attributes do not match their expected
		// values, update and merge the attributes.
		const mergedAttributes = {
			...attributes,
			...getMismatchedAttributes( attributes, remoteData.results, remoteData.blockName, index ),
		};

		// If multiple blocks are being selected, render it as usual.
		if ( hasMultiSelection() ) {
			return <BlockEdit { ...props } attributes={ mergedAttributes } />;
		}

		// If the block is not writable, render it as usual.
		if ( isInSyncedPattern && ! hasEnabledOverrides ) {
			return <BlockEdit { ...props } attributes={ mergedAttributes } />;
		}

		// lookup the title of the remote data block
		const remoteBlockTitle = getBlockTitle( remoteData?.blockName );

		return (
			<BoundBlockEdit
				attributes={ mergedAttributes }
				availableBindings={ availableBindings }
				blockName={ name }
				remoteDataName={ remoteData?.blockName ?? '' }
				remoteDataTitle={ remoteBlockTitle }
				setAttributes={ setAttributes }
			>
				<BlockEdit { ...props } attributes={ mergedAttributes } />
			</BoundBlockEdit>
		);
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
