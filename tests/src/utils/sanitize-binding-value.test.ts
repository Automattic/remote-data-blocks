// @vitest-environment jsdom
import { registerFormatType, unregisterFormatType } from '@wordpress/rich-text';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import {
	sanitizeBindingHTML,
	sanitizeBindingValue,
	sanitizeInlineBindingAttributes,
} from '@/utils/sanitize-binding-value';

const attributes = vi.hoisted( () => ( {
	content: { type: 'rich-text' },
	link: { type: 'string', source: 'attribute', attribute: 'href' },
	url: { type: 'string' },
	id: { type: 'integer' },
} ) );
vi.mock( '@wordpress/blocks', () => ( { getBlockType: () => ( { attributes } ) } ) );

describe( 'editor binding sanitization', () => {
	beforeEach( () => vi.clearAllMocks() );
	it( 'preserves formatted historical Markdown while removing active markup', () => {
		const markup =
			'<p><strong>Bold</strong> <em>italic</em> <a href="https://example.com/?a=1&amp;b=2" target="_blank" rel="noopener">link</a></p>';
		expect( sanitizeBindingValue( markup, 'core/paragraph', 'content' ) ).toBe( markup );
		const hostile =
			'<p onclick="unsafe()">Text</p><script>unsafe()</script><iframe srcdoc="unsafe"></iframe><a href="javascript:unsafe()">link</a>';
		expect( sanitizeBindingHTML( hostile ) ).toBe( '<p>Text</p><a>link</a>' );
	} );
	it( 'retains saved inline binding metadata while cleaning the field markup', () => {
		const inline =
			'<remote-data-blocks-inline-field data-query="{}"><strong>Saved</strong><iframe srcdoc="unsafe"></iframe></remote-data-blocks-inline-field>';
		expect( sanitizeBindingHTML( inline ) ).toBe(
			'<remote-data-blocks-inline-field data-query="{}"><strong>Saved</strong></remote-data-blocks-inline-field>'
		);
	} );
	it( 'cleans previously saved inline attributes without editing unrelated metadata', () => {
		const metadata = { name: 'Saved name' };
		const content =
			'<user-mention data-user="42">Alice</user-mention> <remote-data-blocks-inline-field data-query="{}"><iframe srcdoc="unsafe"></iframe><em>Saved</em></remote-data-blocks-inline-field>';
		const savedAttributes = { content, metadata };
		const safe = sanitizeInlineBindingAttributes( savedAttributes );
		expect( safe.content ).toBe(
			'<user-mention data-user="42">Alice</user-mention> <remote-data-blocks-inline-field data-query="{}"><em>Saved</em></remote-data-blocks-inline-field>'
		);
		expect( safe.metadata ).toBe( metadata );
		expect( savedAttributes.content ).toBe( content );
		const plain = { content: '<em>Ordinary author content</em>', metadata };
		expect( sanitizeInlineBindingAttributes( plain ) ).toBe( plain );
	} );
	it( 'preserves registered custom formatting while enforcing URL and event rules', () => {
		registerFormatType( 'test/user-mention', {
			title: 'User mention',
			tagName: 'user-mention',
			className: null,
			attributes: { user: 'user-id', href: 'href', handler: 'onfocus', document: 'srcdoc' },
			edit: () => null,
		} );
		try {
			expect(
				sanitizeBindingHTML(
					'<user-mention user-id="42" href="https://example.com/">Alice</user-mention>'
				)
			).toBe( '<user-mention user-id="42" href="https://example.com/">Alice</user-mention>' );
			expect(
				sanitizeBindingHTML(
					'<user-mention user-id="42" href="javascript:unsafe()" onfocus="unsafe()" srcdoc="unsafe">Alice</user-mention>'
				)
			).toBe( '<user-mention user-id="42">Alice</user-mention>' );
		} finally {
			unregisterFormatType( 'test/user-mention' );
		}
	} );
	it( 'preserves attributes registered for ordinary HTML format tags', () => {
		registerFormatType( 'test/user-highlight', {
			title: 'User highlight',
			tagName: 'span',
			className: 'user-highlight',
			attributes: { user: 'user-id' },
			edit: () => null,
		} );
		try {
			const html = '<span class="user-highlight" user-id="42">Alice</span>';
			expect( sanitizeBindingHTML( html ) ).toBe( html );
		} finally {
			unregisterFormatType( 'test/user-highlight' );
		}
	} );
	it( 'rejects unsafe target URLs and preserves query strings without HTML encoding', () => {
		expect( sanitizeBindingValue( 'java\nscript:unsafe()', 'test/block', 'link' ) ).toBeNull();
		expect( sanitizeBindingValue( 'data:text/html,unsafe', 'test/block', 'url' ) ).toBeNull();
		const url = 'https://example.com/?a=1&b=2';
		expect( sanitizeBindingValue( url, 'test/block', 'link' ) ).toBe( url );
	} );
	it( 'leaves typed attributes and isolated Remote HTML content intact', () => {
		expect( sanitizeBindingValue( 0, 'test/block', 'id' ) ).toBe( 0 );
		expect( sanitizeBindingValue( false, 'test/block', 'enabled' ) ).toBe( false );
		const embed =
			'<script src="https://example.com/embed.js"></script><iframe src="https://example.com/"></iframe>';
		expect( sanitizeBindingValue( embed, 'remote-data-blocks/remote-html', 'content' ) ).toBe(
			embed
		);
	} );
} );
