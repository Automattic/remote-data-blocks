import { expect, test } from '@wordpress/e2e-test-utils-playwright';

import {
	expectBoundField,
	insertBindingsGarden,
	openBindingChooser,
	selectGardenBlock,
} from './helpers';

test( 'preserves instance text when a pattern definition receives a remote URL binding', async ( {
	admin,
	editor,
	page,
	requestUtils,
} ) => {
	await admin.createNewPost();
	await insertBindingsGarden( page, editor, [
		{
			name: 'core/buttons',
			innerBlocks: [
				{
					name: 'core/button',
					attributes: {
						text: 'Pattern button text',
						url: 'https://example.org/original',
						metadata: {
							name: 'Garden button',
							bindings: { __default: { source: 'core/pattern-overrides' } },
						},
					},
				},
			],
		},
	] );
	await editor.saveDraft();
	const pattern = await requestUtils.rest< { id: number } >( {
		path: '/wp/v2/blocks',
		method: 'POST',
		data: {
			title: 'Remote garden pattern',
			status: 'publish',
			content: await editor.getEditedPostContent(),
		},
	} );
	const instance = await requestUtils.rest< { id: number } >( {
		path: '/wp/v2/posts',
		method: 'POST',
		data: {
			title: 'Garden pattern instance',
			status: 'publish',
			content: `<!-- wp:block ${ JSON.stringify( {
				ref: pattern.id,
				content: { 'Garden button': { text: 'Instance button text' } },
			} ) } /-->`,
		},
	} );

	await admin.editPost( pattern.id );
	await selectGardenBlock( page, 'core/button' );
	await openBindingChooser( page, 'url' );
	await expect( page.getByText( /Applying a remote field replaces it/ ) ).toBeVisible();
	await page.locator( 'select[name="url"]' ).selectOption( 'button_url' );
	await page.getByRole( 'button', { name: 'Cancel', exact: true } ).click();
	await openBindingChooser( page, 'url' );
	await expect( page.locator( 'select[name="url"]' ) ).toHaveValue( '' );
	await page.locator( 'select[name="url"]' ).selectOption( 'button_url' );
	await page.getByRole( 'button', { name: 'Apply', exact: true } ).click();
	await page.evaluate( async () => {
		const browser = window as unknown as {
			wp: { data: { dispatch: ( store: string ) => { savePost: () => Promise< void > } } };
		};
		await browser.wp.data.dispatch( 'core/editor' ).savePost();
	} );
	const saved = await requestUtils.rest< { content: { raw: string } } >( {
		path: `/wp/v2/blocks/${ pattern.id }`,
		params: { context: 'edit' },
	} );
	expect( saved.content.raw ).toContain( '"name":"Garden button"' );
	expect( saved.content.raw ).toContain( '"__default":{"source":"core/pattern-overrides"}' );
	expect( saved.content.raw ).toContain( '"field":"button_url"' );
	await page.reload();
	await selectGardenBlock( page, 'core/button' );
	await expectBoundField( page, 'url', 'button_url' );

	await admin.editPost( instance.id );
	await expect( editor.canvas.locator( '[data-type="core/button"]' ) ).toContainText(
		'Instance button text'
	);
	await selectGardenBlock( page, 'core/button' );
	await expect( page.locator( '.rdb-binding-rows' ) ).toHaveCount( 0 );
	await page.goto( `/?p=${ instance.id }` );
	await expect(
		page.getByRole( 'link', { name: 'Instance button text', exact: true } )
	).toHaveAttribute( 'href', 'https://example.org/garden' );
} );
