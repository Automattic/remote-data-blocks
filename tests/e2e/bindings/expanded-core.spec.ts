import { expect, test } from '@wordpress/e2e-test-utils-playwright';

import {
	getFixtureWordPressVersion,
	getRuntimeBindingAttributes,
	insertBindingsGarden,
	selectGardenBlock,
} from './helpers';

import type { Page } from '@playwright/test';

/** Older runtimes may lack a core capability; modern missing entries must fail. */
async function requireCoreCapability(
	page: Page,
	blockName: string,
	attribute: string,
	introducedIn: [ number, number ]
) {
	const supported = await getRuntimeBindingAttributes( page );
	const version = await getFixtureWordPressVersion( page );
	const [ major = 0, minor = 0 ] = version.split( '.' ).map( Number );
	const isOlderRuntime =
		major < introducedIn[ 0 ] || ( major === introducedIn[ 0 ] && minor < introducedIn[ 1 ] );
	const hasCapability = supported?.[ blockName ]?.includes( attribute );
	test.skip(
		isOlderRuntime && ! hasCapability,
		`${ blockName }.${ attribute } is not exposed by WordPress ${ version }.`
	);
	expect( supported, 'Modern WordPress must expose its authoritative bindings map' ).toBeDefined();
	expect( supported?.[ blockName ] ).toContain( attribute );
}

test.describe( 'expanded core block bindings', () => {
	test.beforeEach( async ( { admin } ) => {
		await admin.createNewPost();
	} );

	test( 'authors, persists and renders list-item content', async ( { page, editor } ) => {
		await requireCoreCapability( page, 'core/list-item', 'content', [ 7, 1 ] );
		await insertBindingsGarden( page, editor, [
			{
				name: 'core/list',
				innerBlocks: [
					{ name: 'core/list-item', attributes: { content: 'List item before binding' } },
				],
			},
		] );
		await selectGardenBlock( page, 'core/list-item' );
		await page.locator( 'select[name="content"]' ).selectOption( 'caption' );
		await expect( editor.canvas.locator( '[data-type="core/list-item"]' ) ).toContainText(
			'Fresh data, familiar blocks.'
		);
		const postId = await editor.publishPost();
		expect( postId ).toBeTruthy();
		expect( await editor.getEditedPostContent() ).toContain( '"field":"caption"' );
		await page.reload();
		await selectGardenBlock( page, 'core/list-item' );
		await expect( page.locator( 'select[name="content"]' ) ).toHaveValue( 'caption' );
		await page.goto( `/?p=${ postId }` );
		await expect( page.locator( '.entry-content .wp-block-list > li' ) ).toHaveText(
			'Fresh data, familiar blocks.'
		);
	} );

	test( 'authors, persists and dynamically renders a remote date', async ( { page, editor } ) => {
		await requireCoreCapability( page, 'core/post-date', 'datetime', [ 6, 9 ] );
		await insertBindingsGarden( page, editor, [
			{
				name: 'core/post-date',
				attributes: { datetime: '2000-01-01T12:00:00', format: 'Y-m-d', isLink: false },
			},
		] );
		await selectGardenBlock( page, 'core/post-date' );
		await page.locator( 'select[name="datetime"]' ).selectOption( 'date' );
		await expect( editor.canvas.locator( '[data-type="core/post-date"]' ) ).toContainText(
			'2026-11-23'
		);
		const postId = await editor.publishPost();
		expect( postId ).toBeTruthy();
		expect( await editor.getEditedPostContent() ).toContain( '"field":"date"' );
		await page.reload();
		await selectGardenBlock( page, 'core/post-date' );
		await expect( page.locator( 'select[name="datetime"]' ) ).toHaveValue( 'date' );
		await page.goto( `/?p=${ postId }` );
		const date = page.locator( '.entry-content .wp-block-post-date time' );
		await expect( date ).toHaveAttribute( 'datetime', '2026-11-23T12:00:00' );
		await expect( date ).toHaveText( '2026-11-23' );
	} );

	test( 'authors and persists a remote URL in a legally nested navigation link', async ( {
		page,
		editor,
	} ) => {
		await requireCoreCapability( page, 'core/navigation-link', 'url', [ 6, 9 ] );
		await insertBindingsGarden( page, editor, [
			{
				name: 'core/navigation',
				attributes: { overlayMenu: 'never' },
				innerBlocks: [
					{
						name: 'core/navigation-link',
						attributes: {
							label: 'Garden destination',
							url: 'https://example.org/before-binding',
							kind: 'custom',
							isTopLevelLink: true,
						},
					},
				],
			},
		] );
		await selectGardenBlock( page, 'core/navigation-link' );
		await page.locator( 'select[name="url"]' ).selectOption( 'button_url' );
		// Navigation is a separately persisted entity. Saving both entities opens
		// a confirmation dialog, then transitions directly to the Publish panel.
		await page
			.getByRole( 'region', { name: 'Editor top bar' } )
			.getByRole( 'button', { name: 'Save', exact: true } )
			.click();
		const saveDialog = page.getByRole( 'dialog', { name: 'Are you ready to save?' } );
		await saveDialog.getByRole( 'button', { name: 'Save', exact: true } ).click();
		await expect( saveDialog ).toBeHidden();
		await page
			.getByRole( 'region', { name: 'Editor publish' } )
			.getByRole( 'button', { name: 'Publish', exact: true } )
			.click();
		await expect(
			page.getByRole( 'button', { name: 'Dismiss this notice' } ).filter( { hasText: 'published' } )
		).toBeVisible();
		const postId = new URL( page.url() ).searchParams.get( 'post' );
		expect( postId ).toBeTruthy();
		await page.reload();
		await expect(
			editor.canvas.locator( '.wp-block-navigation-item[data-type="core/navigation-link"]' )
		).toBeVisible();
		await selectGardenBlock( page, 'core/navigation-link' );
		await expect( page.locator( 'select[name="url"]' ) ).toHaveValue( 'button_url' );
		await page.goto( `/?p=${ postId }` );
		await expect(
			page
				.locator( '.entry-content' )
				.getByRole( 'link', { name: 'Garden destination', exact: true } )
		).toHaveAttribute( 'href', 'https://example.org/garden' );
	} );
} );
