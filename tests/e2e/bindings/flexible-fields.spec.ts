import { expect, test } from '@wordpress/e2e-test-utils-playwright';

import {
	bindField,
	expectBoundField,
	GARDEN_BLOCK,
	getFixtureWordPressVersion,
	getRuntimeBindingAttributes,
	insertBindingsGarden,
	insertRemoteDataBlock,
	openBindingChooser,
	selectGardenBlock,
	STATIC_CARD,
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
		await expect( page.locator( 'select[name="url"]' ) ).toHaveCount( 0 );
		await expect(
			page.getByText( 'No matching fields are available for this attribute.' )
		).toBeVisible();
		await page.getByRole( 'button', { name: 'Close', exact: true } ).click();
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

	test( 'preserves a saved plain-string URL through publish, reopen and render, and lets it be removed', async ( {
		page,
		editor,
	} ) => {
		await insertBindingsGarden( page, editor, [
			{
				name: 'core/buttons',
				innerBlocks: [
					{
						name: 'core/button',
						attributes: {
							text: 'Before binding',
							url: 'https://example.org/before',
							metadata: {
								bindings: {
									url: {
										source: 'remote-data/binding',
										args: { block: GARDEN_BLOCK, field: 'plain_url' },
									},
								},
							},
						},
					},
				],
			},
			{
				name: 'core/image',
				attributes: { url: 'https://example.invalid/placeholder.png', alt: '' },
			},
		] );
		await selectGardenBlock( page, 'core/button' );
		await bindField( page, 'text', 'caption' );
		await selectGardenBlock( page, 'core/image' );
		await openBindingChooser( page, 'url' );
		const imageUrl = page.locator( 'select[name="url"]' );
		await expect( imageUrl.locator( 'option' ) ).toHaveCount( 2 );
		await imageUrl.selectOption( 'image_url' );
		await page.getByRole( 'button', { name: 'Apply', exact: true } ).click();
		await bindField( page, 'alt', 'caption' );
		const postId = await editor.publishPost();
		await page.reload();
		await expect( editor.canvas.locator( '.wp-block-button__link' ) ).toBeVisible();
		const savedContent = await editor.getEditedPostContent();
		expect( savedContent ).toContain( '"field":"plain_url"' );
		await selectGardenBlock( page, 'core/button' );
		await openBindingChooser( page, 'url' );
		const url = page.locator( 'select[name="url"]' );
		await expect( url ).toHaveValue( 'plain_url' );
		await expect( url.locator( 'optgroup[label="Current binding"] option' ) ).toHaveText(
			'Garden URL (plain string)'
		);
		await expect( url.locator( 'optgroup[label="Suggested fields"] option' ) ).toHaveAttribute(
			'value',
			'button_url'
		);
		await expect( url.locator( 'option[value="plain_url"]' ) ).toHaveCount( 1 );
		await expect( page.getByRole( 'button', { name: 'Apply', exact: true } ) ).toBeDisabled();
		await page.getByRole( 'button', { name: 'Cancel', exact: true } ).click();
		expect( await editor.getEditedPostContent() ).toBe( savedContent );
		await expectBoundField( page, 'text', 'caption' );
		const editUrl = page.url();
		await page.goto( `/?p=${ postId }` );
		await expect(
			page.getByRole( 'link', { name: 'Fresh data, familiar blocks.' } )
		).toHaveAttribute( 'href', 'https://example.org/garden/plain-string' );
		await expect( page.getByRole( 'img', { name: 'Fresh data, familiar blocks.' } ) ).toBeVisible();
		await page.goto( editUrl );
		await selectGardenBlock( page, 'core/button' );
		await page.getByRole( 'button', { name: 'Remove Button URL binding' } ).click();
		expect( await editor.getEditedPostContent() ).not.toContain( '"field":"plain_url"' );
		await openBindingChooser( page, 'url' );
		await expect( url.locator( 'option[value="plain_url"]' ) ).toHaveCount( 0 );
	} );

	test( 'uses the custom block HTML attribute schema to distinguish URLs from text', async ( {
		page,
		editor,
	} ) => {
		const supported = await getRuntimeBindingAttributes( page );
		const wordpressVersion = await getFixtureWordPressVersion( page );
		test.skip(
			/^6\.[78](?:\.|$)/.test( wordpressVersion ) && ! supported,
			'WordPress 6.7/6.8 has no supported-attributes filters or generic custom rendering.'
		);
		await insertBindingsGarden( page, editor, [
			{ name: STATIC_CARD, attributes: { destination: 'https://example.org/before' } },
		] );
		await selectGardenBlock( page, STATIC_CARD );
		await openBindingChooser( page, 'displayUrl' );
		const displayUrl = page.locator( 'select[name="displayUrl"]' );
		await expect( displayUrl.locator( 'option[value="caption"]' ) ).toHaveText( 'Garden caption' );
		await displayUrl.selectOption( 'caption' );
		await expect( displayUrl ).toHaveValue( 'caption' );
		await page.getByRole( 'button', { name: 'Apply', exact: true } ).click();
		await openBindingChooser( page, 'destination' );
		const destination = page.locator( 'select[name="destination"]' );
		await expect( destination.locator( 'option[value="plain_url"]' ) ).toHaveCount( 0 );
		await destination.selectOption( 'button_url' );
		await page.getByRole( 'button', { name: 'Apply', exact: true } ).click();
		const postId = await editor.publishPost();
		await page.reload();
		await selectGardenBlock( page, STATIC_CARD );
		await expectBoundField( page, 'destination', 'button_url' );
		await expectBoundField( page, 'displayUrl', 'caption' );
		await page.goto( `/?p=${ postId }` );
		const link = page.getByRole( 'link', { name: 'Read the garden note' } );
		await expect( link ).toHaveAttribute( 'title', 'Fresh data, familiar blocks.' );
		await expect( link ).toHaveAttribute( 'href', 'https://example.org/garden' );
	} );
} );
