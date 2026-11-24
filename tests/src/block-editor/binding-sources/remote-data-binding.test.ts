import { registerBlockBindingsSource } from '@wordpress/blocks';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import '@/block-editor/binding-sources/remote-data-binding';
import { REMOTE_DATA_CONTEXT_KEY } from '@/blocks/remote-data-container/config/constants';
import { BLOCK_BINDING_SOURCE } from '@/config/constants';
import { cloneBlockForPatternPreview, cloneBlockForTemplatePreview } from '@/utils/block-binding';
import { createRemoteDataResults } from '@/utils/remote-data';

const schema = vi.hoisted( () => ( {
	count: { type: 'number' },
	enabled: { type: 'boolean' },
	content: { type: 'string' },
} ) );
vi.mock( '@wordpress/blocks', () => ( {
	registerBlockBindingsSource: vi.fn(),
	getBlockType: () => ( { attributes: schema } ),
	cloneBlock: ( block: object, attributes: object, innerBlocks: object[] ) => ( {
		...block,
		attributes,
		innerBlocks,
	} ),
} ) );
vi.mock( '@wordpress/block-editor', () => ( { store: {} } ) );

type Payload = {
	bindings: Record< string, RemoteDataBlockBinding >;
	clientId?: string;
	context: Record< string, unknown >;
	select: () => object;
};
const getValues = vi.mocked( registerBlockBindingsSource ).mock.calls[ 0 ]?.[ 0 ]
	.getValues as unknown as ( payload: Payload ) => Record< string, unknown >;
const binding = (
	field: string,
	args: Partial< RemoteDataBlockBindingArgs > = {}
): RemoteDataBlockBinding => ( {
	source: BLOCK_BINDING_SOURCE,
	args: { block: 'test/data', field, ...args },
} );
const select = () => ( {
	getBlockName: () => 'test/custom',
	getBlockAttributes: () => ( { count: 7, enabled: true } ),
	getPreviewIndex: () => 1,
} );
const results = createRemoteDataResults( [
	{ count: 9, enabled: true, content: '$9.00' },
	{ count: 0, enabled: false, content: '$0.00' },
] );
const context = { [ REMOTE_DATA_CONTEXT_KEY ]: { results, blockName: 'test/data' } };

describe( 'editor binding output and previews', () => {
	beforeEach( () => {
		vi.clearAllMocks();
	} );
	it( 'preserves zero/false, formatted strings, and row selection', () => {
		expect(
			getValues( {
				clientId: 'id',
				bindings: {
					count: binding( 'count' ),
					enabled: binding( 'enabled' ),
					content: binding( 'content', { label: 'Cost' } ),
				},
				context,
				select,
			} )
		).toEqual( { count: 0, enabled: false, content: 'Cost: $0.00' } );
		expect(
			getValues( {
				clientId: 'id',
				bindings: { count: binding( 'count', { previewIndex: 0, label: 'Ignored' } ) },
				context,
				select,
			} )
		).toEqual( { count: 9 } );
	} );
	it( 'uses saved typed fallbacks on invalid values, not truthiness', () => {
		const invalid = {
			[ REMOTE_DATA_CONTEXT_KEY ]: {
				results: createRemoteDataResults( [ { count: 'invalid', enabled: 'false' } ] ),
			},
		};
		expect(
			getValues( {
				clientId: 'id',
				bindings: {
					count: binding( 'count', { previewIndex: 0 } ),
					enabled: binding( 'enabled', { previewIndex: 0 } ),
				},
				context: invalid,
				select,
			} )
		).toEqual( { count: 7, enabled: true } );
	} );
	it( 'falls back to saved text instead of serializing structured values', () => {
		const structured = {
			[ REMOTE_DATA_CONTEXT_KEY ]: {
				results: createRemoteDataResults( [ { content: { nested: 'value' } } ] ),
			},
		};
		// Saved rich-text content is a RichTextData instance in the editor.
		class RichTextLike {
			toString() {
				return 'Saved content';
			}
		}
		const selectWithSavedContent = () => ( {
			...select(),
			getBlockAttributes: () => ( { content: new RichTextLike() } ),
		} );
		expect(
			getValues( {
				clientId: 'id',
				bindings: { content: binding( 'content', { previewIndex: 0 } ) },
				context: structured,
				select: selectWithSavedContent,
			} )
		).toEqual( { content: 'Saved content' } );
		expect(
			getValues( {
				clientId: 'id',
				bindings: { content: binding( 'content', { previewIndex: 0 } ) },
				context: structured,
				select: () => ( { ...select(), getBlockAttributes: () => ( {} ) } ),
			} )
		).toEqual( { content: '' } );
	} );
	it( 'retains unknown-target legacy strings and omits nonpreview values without results', () => {
		expect(
			getValues( {
				bindings: { count: binding( 'count' ), enabled: binding( 'enabled' ) },
				context,
				select,
			} )
		).toEqual( { count: '0', enabled: 'false' } );
		expect(
			getValues( { clientId: 'id', bindings: { count: binding( 'count' ) }, context: {}, select } )
		).toEqual( {} );
	} );
	it( 'keeps typed preview values including null/false and does not add labels', () => {
		expect(
			getValues( {
				clientId: 'id',
				bindings: {
					count: binding( 'count', { isPreview: true, previewValue: null } ),
					enabled: binding( 'enabled', { isPreview: true, previewValue: false, label: 'Flag' } ),
				},
				context: {},
				select,
			} )
		).toEqual( { count: null, enabled: false } );
	} );
	it( 'clones typed pattern previews and template indexes without changing foreign bindings', () => {
		const foreign = { source: 'other', args: { block: 'other', field: 'other' } };
		const block = {
			name: 'test/custom',
			clientId: 'id',
			isValid: true,
			attributes: {
				metadata: {
					bindings: { count: binding( 'count' ), enabled: binding( 'enabled' ), other: foreign },
				},
			},
			innerBlocks: [],
		};
		const pattern = cloneBlockForPatternPreview( block, results[ 1 ]!, 'test/data' );
		expect(
			( pattern.attributes as typeof block.attributes ).metadata.bindings.count.args.previewValue
		).toBe( 0 );
		expect(
			( pattern.attributes as typeof block.attributes ).metadata.bindings.enabled.args.previewValue
		).toBe( false );
		expect( ( pattern.attributes as typeof block.attributes ).metadata.bindings.other ).toBe(
			foreign
		);
		const structured = createRemoteDataResults( [ { content: { nested: 'value' } } ] )[ 0 ]!;
		const textBlock = {
			...block,
			attributes: { metadata: { bindings: { content: binding( 'content' ) } } },
		};
		expect(
			(
				cloneBlockForPatternPreview( textBlock, structured, 'test/data' )
					.attributes as typeof textBlock.attributes
			 ).metadata.bindings.content.args.previewValue
		).toBe( '' );
		const template = cloneBlockForTemplatePreview( block, results[ 1 ]!, 'test/data', 1 );
		expect(
			( template.attributes as typeof block.attributes ).metadata.bindings.count.args.previewIndex
		).toBe( 1 );
	} );
} );
