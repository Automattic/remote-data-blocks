import { expect, test } from '@wordpress/e2e-test-utils-playwright';

import {
	getFixtureWordPressVersion,
	getRuntimeBindingAttributes,
	insertBindingsGarden,
	selectGardenBlock,
	STATIC_CARD,
	STOCK_CARD,
} from './helpers';

test.describe( 'remote block bindings', () => {
	test.beforeEach( async ( { admin } ) => {
		await admin.createNewPost();
	} );

	test( 'authors existing core bindings, saves, reloads and renders on the frontend', async ( {
		editor,
		page,
	} ) => {
		await insertBindingsGarden( page, editor, [
			{ name: 'core/paragraph', attributes: { content: 'Paragraph before binding' } },
			{ name: 'core/heading', attributes: { content: 'Heading before binding' } },
			{
				name: 'core/buttons',
				innerBlocks: [ { name: 'core/button', attributes: { text: 'Button before binding' } } ],
			},
			{
				name: 'core/image',
				attributes: { url: 'https://example.invalid/placeholder.png', alt: '' },
			},
		] );

		async function bindContent( name: string ) {
			await selectGardenBlock( page, name );
			await page.locator( 'select[name="content"]' ).selectOption( 'title' );
			await expect( editor.canvas.locator( `[data-type="${ name }"]` ) ).toContainText(
				'Local garden collection'
			);
		}
		await bindContent( 'core/paragraph' );
		await bindContent( 'core/heading' );
		await selectGardenBlock( page, 'core/button' );
		// Semantic types guide the choice without hiding other compatible fields.
		await expect(
			page.locator(
				'select[name="url"] optgroup[label="Suggested fields"] option[value="button_url"]'
			)
		).toHaveText( 'Garden link' );
		await expect(
			page.locator(
				'select[name="url"] optgroup[label="Other compatible fields"] option[value="image_url"]'
			)
		).toHaveCount( 1 );
		await page.locator( 'select[name="url"]' ).selectOption( 'button_url' );
		await page.locator( 'select[name="text"]' ).selectOption( 'button_text' );
		await selectGardenBlock( page, 'core/image' );
		await page.locator( 'select[name="url"]' ).selectOption( 'image_url' );
		await page.locator( 'select[name="alt"]' ).selectOption( 'image_alt' );
		await page.locator( 'select[name="title"]' ).selectOption( 'title' );

		const supported = await getRuntimeBindingAttributes( page );
		if ( supported?.[ 'core/image' ]?.includes( 'caption' ) ) {
			await page.locator( 'select[name="caption"]' ).selectOption( 'caption' );
		}
		const postId = await editor.publishPost();
		expect( postId ).toBeTruthy();
		const saved = await editor.getEditedPostContent();
		expect( saved ).toContain( '"source":"remote-data/binding"' );
		expect( saved ).toContain( '"field":"button_url"' );
		await page.reload();
		await selectGardenBlock( page, 'core/button' );
		await expect( page.locator( 'select[name="url"]' ) ).toHaveValue( 'button_url' );
		await expect( page.locator( 'select[name="text"]' ) ).toHaveValue( 'button_text' );
		await page.goto( `/?p=${ postId }` );
		await expect(
			page.getByRole( 'heading', { name: 'Local garden collection', exact: true } )
		).toBeVisible();
		await expect(
			page.getByRole( 'link', { name: 'Explore the garden', exact: true } )
		).toHaveAttribute( 'href', 'https://example.org/garden' );
		await expect(
			page.getByRole( 'img', { name: 'A deterministic illustrated garden' } )
		).toHaveAttribute( 'src', /bindings-demo\/garden\.svg$/ );
		await expect(
			page.getByRole( 'img', { name: 'A deterministic illustrated garden' } )
		).toHaveAttribute( 'title', 'Local garden collection' );
		if ( supported?.[ 'core/image' ]?.includes( 'caption' ) ) {
			await expect( page.locator( 'figcaption' ) ).toHaveText( 'Fresh data, familiar blocks.' );
		}
	} );

	test( 'uses both WordPress filters for static strings and dynamic zero/false', async ( {
		editor,
		page,
	} ) => {
		const supported = await getRuntimeBindingAttributes( page );
		// Only a legacy runtime without the capability map may skip custom rendering.
		// A missing modern map/filter is a failure, not a capability skip.
		const wordpressVersion = await getFixtureWordPressVersion( page );
		if ( process.env.RDB_E2E_LEGACY === '1' ) {
			expect( wordpressVersion ).toMatch( /^6\.7(?:\.|$)/ );
		}
		test.skip(
			/^6\.[78](?:\.|$)/.test( wordpressVersion ) && ! supported,
			'WordPress 6.7/6.8 has no supported-attributes filters or generic custom rendering.'
		);
		expect( supported?.[ STATIC_CARD ] ).toContain( 'botanicalNote' );
		expect( supported?.[ STOCK_CARD ] ).toEqual(
			expect.arrayContaining( [ 'plantCount', 'isAvailable' ] )
		);
		await insertBindingsGarden( page, editor, [ { name: STATIC_CARD }, { name: STOCK_CARD } ] );
		await selectGardenBlock( page, STATIC_CARD );
		await page.locator( 'select[name="botanicalNote"]' ).selectOption( 'caption' );
		await expect( editor.canvas.locator( `[data-type="${ STATIC_CARD }"]` ) ).toContainText(
			'Fresh data, familiar blocks.'
		);
		await selectGardenBlock( page, STOCK_CARD );
		await page.locator( 'select[name="plantCount"]' ).selectOption( 'quantity' );
		await page.locator( 'select[name="isAvailable"]' ).selectOption( 'available' );
		const stock = editor.canvas.locator( `[data-type="${ STOCK_CARD }"]` );
		await expect( stock ).toContainText( 'Remaining plants: 0' );
		await expect( stock ).toContainText( 'Available today: false' );
		const postId = await editor.publishPost();
		const saved = await editor.getEditedPostContent();
		expect( saved ).toContain( '"field":"quantity"' );
		expect( saved ).toContain( '"field":"available"' );
		await page.reload();
		await selectGardenBlock( page, STOCK_CARD );
		await expect( page.locator( 'select[name="plantCount"]' ) ).toHaveValue( 'quantity' );
		await expect( page.locator( 'select[name="isAvailable"]' ) ).toHaveValue( 'available' );
		await expect( editor.canvas.locator( `[data-type="${ STOCK_CARD }"]` ) ).toContainText(
			'Remaining plants: 0'
		);
		await page.goto( `/?p=${ postId }` );
		await expect( page.locator( '.botanical-note' ) ).toHaveText( 'Fresh data, familiar blocks.' );
		await expect( page.locator( '.plant-count' ) ).toHaveText( '0' );
		await expect( page.locator( '.plant-available' ) ).toHaveText( 'false' );
	} );
} );
