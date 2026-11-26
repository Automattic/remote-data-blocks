// @vitest-environment jsdom
import { registerBlockBindingsSource } from '@wordpress/blocks';
import { registerFormatType, unregisterFormatType } from '@wordpress/rich-text';
import { describe, expect, it, vi } from 'vitest';

import '@/block-editor/binding-sources/remote-data-binding';
import { REMOTE_DATA_CONTEXT_KEY } from '@/blocks/remote-data-container/config/constants';
import { BLOCK_BINDING_SOURCE } from '@/config/constants';
import { createRemoteDataResults } from '@/utils/remote-data';

vi.mock( '@wordpress/blocks', () => ( {
	registerBlockBindingsSource: vi.fn(),
	getBlockType: () => ( {
		attributes: { content: { type: 'rich-text' }, url: { type: 'string' } },
	} ),
} ) );
vi.mock( '@wordpress/block-editor', () => ( { store: {} } ) );

const getValues = vi.mocked( registerBlockBindingsSource ).mock.calls[ 0 ]![ 0 ].getValues!;
const binding = ( field: string, args = {} ) => ( {
	source: BLOCK_BINDING_SOURCE,
	args: { block: 'test/data', field, ...args },
} );

function resolve(
	fields: Record< string, unknown >,
	target = 'content',
	args = {},
	savedContent = '<strong>Saved copy</strong>'
) {
	return getValues( {
		clientId: 'test',
		bindings: { [ target ]: binding( target, args ) },
		context: { [ REMOTE_DATA_CONTEXT_KEY ]: { results: createRemoteDataResults( [ fields ] ) } },
		select: () => ( {
			getBlockName: () => ( target === 'url' ? 'core/navigation-link' : 'core/paragraph' ),
			getPreviewIndex: () => 0,
			getBlockAttributes: () => ( { content: savedContent, url: '/saved' } ),
		} ),
	} as never );
}

describe( 'legacy editor binding security', () => {
	it( 'sanitizes legacy Markdown and preview values while preserving formatting', () => {
		expect(
			resolve( { content: '<strong>Safe</strong><iframe srcdoc="unsafe"></iframe>' } )
		).toEqual( {
			content: '<strong>Safe</strong>',
		} );
		expect(
			resolve( {}, 'content', {
				isPreview: true,
				previewValue: '<em>Preview</em><script>unsafe()</script>',
			} )
		).toEqual( {
			content: '<em>Preview</em>',
		} );
	} );
	it( 'uses a safe saved URL when the live plain string has a blocked protocol', () => {
		expect( resolve( { url: 'javascript:unsafe()' }, 'url' ) ).toEqual( { url: '/saved' } );
	} );
	it( 'passes through live URLs allowed by the WordPress protocol policy', () => {
		const url = 'irc://example.com/channel';
		const content = `<a href="${ url }">Channel</a>`;
		expect( resolve( { url }, 'url' ) ).toEqual( { url } );
		expect( resolve( { content } ) ).toEqual( { content } );
	} );
	it( 'preserves a registered author format when a bound value falls back', () => {
		registerFormatType( 'test/user-mention', {
			title: 'User mention',
			tagName: 'user-mention',
			className: null,
			attributes: { user: 'user-id' },
			edit: () => null,
		} );
		try {
			const content = '<user-mention user-id="42">Alice</user-mention>';
			expect( resolve( { content: { nested: true } }, 'content', {}, content ) ).toEqual( {
				content,
			} );
		} finally {
			unregisterFormatType( 'test/user-mention' );
		}
	} );
	it( 'keeps safe markup in saved fallbacks', () => {
		expect( resolve( { content: { nested: true } } ) ).toEqual( {
			content: '<strong>Saved copy</strong>',
		} );
	} );
} );
