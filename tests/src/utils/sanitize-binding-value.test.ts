// @vitest-environment jsdom
import { registerFormatType, unregisterFormatType } from '@wordpress/rich-text';
import { readFileSync } from 'node:fs';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import {
	sanitizeBindingHTML,
	sanitizeBindingValue,
	sanitizeInlineBindingAttributes,
} from '@/utils/sanitize-binding-value';

const attributes = vi.hoisted( () => ( {
	content: { type: 'rich-text' },
	link: { type: 'string', source: 'attribute', attribute: 'href' },
	titleUrl: { type: 'string', source: 'attribute', attribute: 'title' },
	displayUrl: { type: 'string', source: 'html' },
	statusUrl: { type: 'string', source: 'text' },
	resource: { type: 'string', source: 'attribute', attribute: 'data-resource' },
	url: { type: 'string' },
	summaryUrl: { type: 'string' },
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
	it( 'preserves safe inline formatting and removes stylesheets and form controls', () => {
		const markup =
			'<p><style>.unused{color:green}</style><form><button>Label</button></form><mark class="has-inline-color" style="color:rgb(1, 2, 3);position:fixed">Text</mark></p>';
		const html = document.createElement( 'div' );
		html.innerHTML = sanitizeBindingHTML( markup );
		expect( html.querySelector( 'style, form, button' ) ).toBeNull();
		expect( html.querySelector( 'mark' ) ).toHaveAttribute( 'style', 'color:rgb(1, 2, 3)' );
		expect( html ).toHaveTextContent( 'LabelText' );
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
	it( 'retains the bundled blueprint’s saved inline query JSON', () => {
		const blueprint = readFileSync( 'example/assets/blueprint-content.wxr', 'utf8' );
		const content = blueprint.match(
			/<remote-data-blocks-inline-field\b[^>]*>.*?<\/remote-data-blocks-inline-field>/
		)?.[ 0 ];
		expect( content ).toBeDefined();

		const original = document.createElement( 'div' );
		original.innerHTML = content!;
		const query = original.firstElementChild?.getAttribute( 'data-query' );
		expect( JSON.parse( query! ) ).toMatchObject( { selectedField: 'title', type: 'field' } );

		const saved = document.createElement( 'div' );
		saved.innerHTML = String( sanitizeInlineBindingAttributes( { content } ).content );
		expect( saved.firstElementChild ).toHaveAttribute( 'data-query', query );
		expect( saved ).toHaveTextContent( 'Lunch' );
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
			expect( sanitizeBindingHTML( '<span user-id="42">Alice</span>' ) ).toBe(
				'<span>Alice</span>'
			);
		} finally {
			unregisterFormatType( 'test/user-highlight' );
		}
	} );
	it( 'rejects unsafe target URLs and preserves query strings without HTML encoding', () => {
		expect( sanitizeBindingValue( 'java\nscript:unsafe()', 'test/block', 'link' ) ).toBeNull();
		expect( sanitizeBindingValue( 'Status: Ready', 'core/navigation-link', 'url' ) ).toBeNull();
		expect( sanitizeBindingValue( 'Status: Ready', 'core/navigation-submenu', 'url' ) ).toBeNull();
		const url = 'https://example.com/?a=1&b=2';
		expect( sanitizeBindingValue( url, 'test/block', 'link' ) ).toBe( url );
	} );
	it( 'allows WordPress protocols in both URL attributes and RichText links', () => {
		const url = 'irc://example.com/channel';
		const html = `<a href="${ url }">Channel</a>`;
		expect( sanitizeBindingValue( url, 'test/block', 'link' ) ).toBe( url );
		expect( sanitizeBindingHTML( html ) ).toBe( html );
		expect( sanitizeBindingHTML( '<a href="java&#10;script:unsafe()">Link</a>' ) ).toBe(
			'<a>Link</a>'
		);
		expect( sanitizeBindingHTML( '<img src="data:image/png;base64,AA==" alt="Example">' ) ).toBe(
			'<img alt="Example">'
		);
	} );
	it( 'uses the sourced HTML attribute to distinguish text from Core and site URI attributes', () => {
		const policy = window.REMOTE_DATA_BLOCKS_EDITOR_HTML!;
		vi.stubGlobal( 'REMOTE_DATA_BLOCKS_EDITOR_HTML', {
			...policy,
			uriAttributes: [ ...policy.uriAttributes, 'data-resource' ],
		} );
		try {
			expect( sanitizeBindingValue( 'Status: Ready', 'test/block', 'titleUrl' ) ).toBe(
				'Status: Ready'
			);
			expect( sanitizeBindingValue( 'irc://example.com/channel', 'test/block', 'resource' ) ).toBe(
				'irc://example.com/channel'
			);
			expect( sanitizeBindingValue( 'example:resource', 'test/block', 'resource' ) ).toBeNull();
		} finally {
			vi.unstubAllGlobals();
		}
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
	it( 'preserves prose in custom attributes without an HTML source', () => {
		expect( sanitizeBindingValue( 'Status: Ready', 'test/block', 'summaryUrl' ) ).toBe(
			'Status: Ready'
		);
		expect( sanitizeBindingValue( 'Status: Ready', 'test/block', 'url' ) ).toBe( 'Status: Ready' );
	} );

	it( 'preserves prose in HTML- and text-sourced attributes with URL-like names', () => {
		expect( sanitizeBindingValue( 'Status: <em>Ready</em>', 'test/block', 'displayUrl' ) ).toBe(
			'Status: <em>Ready</em>'
		);
		expect( sanitizeBindingValue( 'Status: Ready', 'test/block', 'statusUrl' ) ).toBe(
			'Status: Ready'
		);
	} );
} );
