// @ts-expect-error -- Temporary registerBlockBindingsSource type error workaround for WordPress 6.7
import { registerBlockType, registerBlockBindingsSource } from '@wordpress/blocks';
import { addFilter } from '@wordpress/hooks';
import { registerFormatType } from '@wordpress/rich-text';

import { REMOTE_DATA_CONTEXT_KEY } from './config/constants';
import { formatTypeSettings } from '@/blocks/remote-data-container/components/field-shortcode';
import { FieldShortcodeButton } from '@/blocks/remote-data-container/components/field-shortcode/FieldShortcodeButton';
import { Edit } from '@/blocks/remote-data-container/edit';
import { withBlockBindingShim } from '@/blocks/remote-data-container/filters/withBlockBinding';
import { Save } from '@/blocks/remote-data-container/save';
import { BLOCK_BINDING_SOURCE } from '@/config/constants';
import { getBlocksConfig } from '@/utils/localized-block-data';
import './style.scss';
import { getClassName } from '@/utils/string';

// Register a unique block definition for each of the context blocks.
Object.values( getBlocksConfig() ).forEach( blockConfig => {
	registerBlockType< RemoteDataBlockAttributes >( blockConfig.name, {
		...blockConfig.settings,
		attributes: {
			remoteData: {
				type: 'object',
			},
		},
		edit: Edit,
		save: Save,
	} );
} );

// Register the field shortcode format type.
registerFormatType( 'remote-data-blocks/field-shortcode', {
	...formatTypeSettings,
	edit: FieldShortcodeButton,
} );

/**
 * Use a filter to wrap the block edit component with our block binding HOC.
 * We are intentionally using the `blocks.registerBlockType` filter instead of
 * `editor.BlockEdit` so that we can make sure our HOC is applied after any
 * other HOCs from Core -- specifically this one, which injects the binding label
 * as the attribute value:
 *
 * https://github.com/WordPress/gutenberg/blob/f56dbeb9257c19acf6fbd8b45d87ae8a841624da/packages/block-editor/src/hooks/use-bindings-attributes.js#L159
 */
addFilter(
	'blocks.registerBlockType',
	'remote-data-blocks/withBlockBinding',
	withBlockBindingShim,
	5 // Ensure this runs before core filters
);

// eslint-disable-next-line -- Temporary registerBlockBindingsSource type error workaround for WordPress 6.7
registerBlockBindingsSource( {
	name: BLOCK_BINDING_SOURCE,
	label: 'Remote Data Binding',
	usesContext: [ REMOTE_DATA_CONTEXT_KEY ],
	// eslint-disable-next-line -- Temporary registerBlockBindingsSource type error workaround for WordPress 6.7
	getValues( { select, clientId, context, bindings }: any ) {
		if ( context[ REMOTE_DATA_CONTEXT_KEY ] === undefined ) {
			return {};
		}

		const {
			[ REMOTE_DATA_CONTEXT_KEY ]: { result, remoteDataBlockName },
		} = context;

		const boundAttributes = getBoundAttributeEntries( bindings, remoteDataBlockName );

		const mappedAttributes = boundAttributes.map( ( [ target, binding ] ) => [
			target,
			getExpectedAttributeValue( result, binding.args ),
		] );

		const attributes = Object.fromEntries(
			mappedAttributes
		) as Partial< RemoteDataInnerBlockAttributes >;

		return attributes;
	},
} );

function getBoundAttributeEntries(
	bindings: Record< string, RemoteDataBlockBinding > | undefined,
	remoteDataBlockName: string
): [ string, RemoteDataBlockBinding ][] {
	return Object.entries( bindings ?? {} ).filter(
		( [ _target, binding ] ) => binding.args?.block === remoteDataBlockName
	);
}

function getExpectedAttributeValue(
	result?: Record< string, string >,
	args?: RemoteDataBlockBindingArgs
): string | null {
	if ( ! args?.field || ! result?.[ args.field ] ) {
		return null;
	}

	let expectedValue = result[ args.field ];
	if ( args.label ) {
		const labelClass = getClassName( 'block-label' );
		expectedValue = `<span class="${ labelClass }">${ args.label }</span> ${ expectedValue }`;
	}

	return expectedValue ?? null;
}
