import { expect, test } from '@wordpress/e2e-test-utils-playwright';

interface EditorWindow extends Window {
	wp: {
		blocks: typeof import('@wordpress/blocks');
		richText: typeof import('@wordpress/rich-text');
	};
}

test( 'preserves a registered custom format when editing beside a saved inline binding', async ( {
	admin,
	editor,
	page,
} ) => {
	await page.addInitScript( () => {
		if ( window !== window.top ) {
			return;
		}

		const registerFormat = () => {
			const richText = ( window as EditorWindow ).wp?.richText;
			if ( ! richText ) {
				window.setTimeout( registerFormat, 10 );
				return;
			}
			richText.registerFormatType( 'rdb-test/mention', {
				title: 'Test mention',
				tagName: 'user-mention',
				className: null,
				attributes: {
					user: 'user-id',
					mention: 'data-mention',
					href: 'href',
					context: 'data-wp-context',
				},
				edit: () => null,
			} );
		};
		registerFormat();
	} );
	await admin.createNewPost();
	const mentionData = JSON.stringify( { id: 'team:alice' } );
	await editor.insertBlock( {
		name: 'core/paragraph',
		attributes: {
			content: `Before <remote-data-blocks-inline-field data-query="{}"><strong data-mention='${ mentionData }'><user-mention user-id="42" data-mention='${ mentionData }' href="file:///fixture" data-wp-context="{}">Alice</user-mention></strong><iframe title="Fixture"></iframe></remote-data-blocks-inline-field> <remote-data-blocks-inline-field data-query="{}"><iframe title="Empty preview"></iframe></remote-data-blocks-inline-field> after`,
		},
	} );

	const fields = editor.canvas.locator( 'remote-data-blocks-inline-field' );
	const field = fields.first();
	const mention = field.locator( 'strong user-mention' );
	await expect( mention ).toHaveText( 'Alice' );
	await expect( mention ).toHaveAttribute( 'user-id', '42' );
	await expect( mention ).toHaveAttribute( 'data-mention', mentionData );
	await expect( mention ).not.toHaveAttribute( 'href' );
	await expect( mention ).not.toHaveAttribute( 'data-wp-context' );
	await expect( fields.locator( 'iframe' ) ).toHaveCount( 0 );
	await expect( field.locator( 'strong' ) ).not.toHaveAttribute( 'data-mention' );

	const paragraph = editor.canvas.locator( 'p[contenteditable="true"]' ).filter( { has: field } );
	await paragraph.click();
	await paragraph.press( 'End' );
	await page.keyboard.insertText( '!' );
	await expect( paragraph ).toContainText( 'after!' );
	await editor.saveDraft();
	await page.reload();

	await expect(
		editor.canvas.locator( 'remote-data-blocks-inline-field[data-query]' )
	).toHaveCount( 2 );
	await expect( mention ).toHaveText( 'Alice' );
	await expect( mention ).toHaveAttribute( 'user-id', '42' );
	await expect( mention ).toHaveAttribute( 'data-mention', mentionData );
	await expect( mention ).not.toHaveAttribute( 'href' );
	await expect( mention ).not.toHaveAttribute( 'data-wp-context' );
	await expect( fields.locator( 'iframe' ) ).toHaveCount( 0 );
	await expect( field.locator( 'strong' ) ).not.toHaveAttribute( 'data-mention' );
	await expect( paragraph ).toContainText( 'after!' );
} );

test( 'filters binding HTML and checks URLs by their rendering context', async ( {
	admin,
	page,
} ) => {
	await admin.createNewPost();
	const result = await page.evaluate( () => {
		const { blocks } = ( window as EditorWindow ).wp;
		const name = 'rdb-test/plain-attributes';
		blocks.registerBlockType( name, {
			title: 'Plain attributes',
			category: 'text',
			attributes: { summaryUrl: { type: 'string' }, url: { type: 'string' } },
			edit: () => null,
			save: () => null,
		} );
		const source = blocks.getBlockBindingsSource( 'remote-data/binding' );
		const resolve = ( blockName: string, attribute: string, value: string ) =>
			source?.getValues?.( {
				clientId: 'rdb-test',
				bindings: { [ attribute ]: { args: { block: name, field: 'example' } } },
				context: {
					'remote-data-blocks/remoteData': {
						results: [
							{ uuid: '0', result: { example: { name: 'Example', type: 'string', value } } },
						],
					},
				},
				select: () => ( {
					getBlockName: () => blockName,
					getPreviewIndex: () => 0,
					getBlockAttributes: () => ( { [ attribute ]: '/saved' } ),
				} ),
			} as never )?.[ attribute ];

		return {
			formatted: resolve(
				'core/paragraph',
				'content',
				'<strong>Garden</strong><iframe title="Fixture"></iframe>'
			),
			plainNamedUrl: resolve( name, 'summaryUrl', 'Status: Ready' ),
			buttonFallback: resolve( 'core/button', 'url', 'fixture:unsupported' ),
			navigationLink: resolve( 'core/navigation-link', 'url', '/garden' ),
		};
	} );
	expect( result ).toEqual( {
		formatted: '<strong>Garden</strong>',
		plainNamedUrl: 'Status: Ready',
		buttonFallback: '/saved',
		navigationLink: '/garden',
	} );
} );
