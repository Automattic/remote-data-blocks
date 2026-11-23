import { expect, test } from '@wordpress/e2e-test-utils-playwright';

import {
	bindField,
	expectBoundField,
	insertBindingsGarden,
	insertRemoteDataBlock,
	openBindingChooser,
	selectGardenBlock,
} from './helpers';

test.describe( 'flexible compatible field choices', () => {
	test.beforeEach( async ( { admin } ) => {
		await admin.createNewPost();
	} );

	test( 'binds actual Conference Event text without semantic field types', async ( {
		page,
		editor,
	} ) => {
		await insertRemoteDataBlock(
			page,
			editor,
			'remote-data-blocks/conference-event',
			[ { record_id: 'rec2uM1inwuvs9oPz' } ],
			[
				{
					name: 'core/buttons',
					innerBlocks: [ { name: 'core/button', attributes: { text: 'Before binding' } } ],
				},
			]
		);
		await selectGardenBlock( page, 'core/button' );
		await openBindingChooser( page, 'text' );
		const text = page.locator( 'select[name="text"]' );
		await expect(
			text.locator( 'optgroup[label="Compatible fields"] option[value="title"]' )
		).toHaveText( 'Title' );
		await expect( text.locator( 'optgroup[label="Suggested fields"]' ) ).toHaveCount( 0 );
		await text.selectOption( 'title' );
		await page.getByRole( 'button', { name: 'Apply', exact: true } ).click();
		await openBindingChooser( page, 'url' );
		await expect( page.locator( 'select[name="url"] option[value="title"]' ) ).toHaveCount( 1 );
		await page.getByRole( 'button', { name: 'Cancel', exact: true } ).click();
		await expect( editor.canvas.locator( '.wp-block-button__link' ) ).toBeVisible();
		const postId = await editor.publishPost();
		await page.reload();
		await selectGardenBlock( page, 'core/button' );
		await expectBoundField( page, 'text', 'title' );
		await page.goto( `/?p=${ postId }` );
		await expect( page.locator( '.entry-content .wp-block-button__link' ) ).toHaveText(
			'Community building workshop'
		);
	} );

	test( 'chooses ordinary strings while semantic suggestions remain available, then saves and renders', async ( {
		page,
		editor,
	} ) => {
		await insertBindingsGarden( page, editor, [
			{
				name: 'core/buttons',
				innerBlocks: [
					{
						name: 'core/button',
						attributes: { text: 'Before binding', url: 'https://example.org/before' },
					},
				],
			},
			{
				name: 'core/image',
				attributes: { url: 'https://example.invalid/placeholder.png', alt: '' },
			},
		] );
		await selectGardenBlock( page, 'core/button' );
		await openBindingChooser( page, 'url' );
		const url = page.locator( 'select[name="url"]' );
		await expect(
			url.locator( 'optgroup[label="Suggested fields"] option[value="button_url"]' )
		).toHaveCount( 1 );
		await expect(
			url.locator( 'optgroup[label="Other compatible fields"] option[value="plain_url"]' )
		).toHaveCount( 1 );
		await url.selectOption( 'plain_url' );
		await page.getByRole( 'button', { name: 'Apply', exact: true } ).click();
		await bindField( page, 'text', 'caption' );
		await selectGardenBlock( page, 'core/image' );
		await bindField( page, 'url', 'image_url' );
		await bindField( page, 'alt', 'caption' );
		const postId = await editor.publishPost();
		await page.reload();
		await selectGardenBlock( page, 'core/button' );
		await expectBoundField( page, 'url', 'plain_url' );
		await expectBoundField( page, 'text', 'caption' );
		await page.goto( `/?p=${ postId }` );
		await expect(
			page.getByRole( 'link', { name: 'Fresh data, familiar blocks.' } )
		).toHaveAttribute( 'href', 'https://example.org/garden/plain-string' );
		await expect( page.getByRole( 'img', { name: 'Fresh data, familiar blocks.' } ) ).toBeVisible();
	} );
} );
