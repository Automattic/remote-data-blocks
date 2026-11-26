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
import { getBoundBlockClassName } from '@/utils/block-binding';
import { useSupportedBindingAttributes } from '@/utils/block-bindings-support';
import { getBlockAvailableBindings, getBlockTitle } from '@/utils/localized-block-data';

interface BoundBlockEditProps {
	isSelected: boolean;
	attributes: RemoteDataInnerBlockAttributes;
	availableBindings: AvailableBindings;
	blockName: string;
	children: React.JSX.Element;
	remoteDataName: string;
	supportedAttributes: string[];
	remoteDataTitle: string;
	setAttributes: ( attributes: RemoteDataInnerBlockAttributes ) => void;
}

function BoundBlockEdit( props: BoundBlockEditProps ) {
	const {
		attributes,
		availableBindings,
		blockName,
		isSelected,
		remoteDataName,
		remoteDataTitle,
		setAttributes,
		supportedAttributes,
	} = props;
	const existingBindings = attributes.metadata?.bindings ?? {};
	// Pattern instances use this name to look up their saved overrides.
	const hasPatternBinding = Object.values( existingBindings ).some(
		binding => binding.source === PATTERN_OVERRIDES_BINDING_SOURCE
	);
	const existingName = attributes.metadata?.name;

	function removeBinding( target: string ) {
		const { [ target ]: _remove, ...newBindings } = existingBindings;
		setAttributes( {
			metadata: {
				...attributes.metadata,
				bindings: newBindings,
				name: hasPatternBinding ? existingName : undefined,
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
				name: hasPatternBinding ? existingName : availableBindings[ args.field ]?.name,
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
						isSelected={ isSelected }
						supportedAttributes={ supportedAttributes }
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
	return ( props: BlockEditProps< RemoteDataInnerBlockAttributes > ) => {
		const { attributes, context, name, setAttributes } = props;
		const { remoteData } = useRemoteDataContext( context );
		const supportedAttributes = useSupportedBindingAttributes( name );
		const availableBindings = getBlockAvailableBindings( remoteData?.blockName ?? '' );
		const hasAvailableBindings = Boolean( Object.keys( availableBindings ).length );
		const { hasMultiSelection } = useSelect< BlockEditorStoreSelectors >( blockEditorStore );
		const hasExistingBinding = Object.values( attributes.metadata?.bindings ?? {} ).some(
			binding => binding.source === BLOCK_BINDING_SOURCE
		);

		// If the block does not have a remote data context, render it as usual.
		if (
			! remoteData ||
			( ! hasAvailableBindings && ! hasExistingBinding ) ||
			( ! supportedAttributes.length && ! hasExistingBinding )
		) {
			return <BlockEdit { ...props } />;
		}

		// A pattern instance supplies context even when no override values are set.
		// Core rejects metadata writes inside instances using a default override.

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
		if (
			isInSyncedPattern &&
			( ! hasEnabledOverrides ||
				attributes.metadata?.bindings?.__default?.source === PATTERN_OVERRIDES_BINDING_SOURCE )
		) {
			return <BlockEdit { ...props } attributes={ attributes } />;
		}

		// lookup the title of the remote data block
		const remoteBlockTitle = getBlockTitle( remoteData?.blockName );

		return (
			<BoundBlockEdit
				key={ props.clientId }
				isSelected={ props.isSelected }
				attributes={ attributes }
				availableBindings={ availableBindings }
				blockName={ name }
				supportedAttributes={ supportedAttributes }
				remoteDataName={ remoteData?.blockName ?? '' }
				remoteDataTitle={ remoteBlockTitle }
				setAttributes={ setAttributes }
			>
				<BlockEdit { ...props } attributes={ attributes } />
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
