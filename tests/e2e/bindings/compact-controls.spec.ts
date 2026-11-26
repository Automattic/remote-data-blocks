import { expect, test } from '@wordpress/e2e-test-utils-playwright';

import {
	bindField,
	insertBindingsGarden,
	insertRemoteDataBlock,
	openBindingChooser,
	selectGardenBlock,
} from './helpers';

import type { Page } from '@playwright/test';

async function getButtonBindings( page: Page ) {
	return page.evaluate( () => {
		const browser = window as unknown as {
			wp: {
				data: {
					select: ( name: string ) => {
						getClientIdsWithDescendants: () => string[];
						getBlockName: ( id: string ) => string;
						getBlockAttributes: ( id: string ) => RemoteDataInnerBlockAttributes;
					};
				};
			};
		};
		const store = browser.wp.data.select( 'core/block-editor' );
		const id = store
			.getClientIdsWithDescendants()
			.find( clientId => store.getBlockName( clientId ) === 'core/button' );
		return id ? store.getBlockAttributes( id ).metadata?.bindings ?? {} : {};
	} );
}

async function capture( page: Page, name: string ) {
	// Optional review evidence goes outside the repository; never a visual snapshot test.
	if ( process.env.RDB_SCREENSHOT_DIR ) {
		await page.screenshot( { path: `${ process.env.RDB_SCREENSHOT_DIR }/${ name }.png` } );
	}
}

const buttonTemplate = [
	{
		name: 'core/buttons',
		innerBlocks: [ { name: 'core/button', attributes: { text: 'Before binding' } } ],
	},
];

test.describe( 'compact binding controls', () => {
	test.beforeEach( async ( { admin, page } ) => {
		await page.setViewportSize( { width: 1280, height: 900 } );
		await admin.createNewPost();
	} );

	test( 'edits one draft at a time, applies, cancels and returns keyboard focus', async ( {
		page,
		editor,
	} ) => {
		await insertBindingsGarden( page, editor, [
			...buttonTemplate,
			{
				name: 'core/buttons',
				innerBlocks: [ { name: 'core/button', attributes: { text: 'Second button' } } ],
			},
		] );
		await selectGardenBlock( page, 'core/button' );
		await expect( page.locator( '.rdb-binding-rows select' ) ).toHaveCount( 0 );
		await capture( page, 'compact-unbound' );
		const trigger = page.getByRole( 'button', { name: 'Bind Button Text', exact: true } );
		await trigger.focus();
		await page.keyboard.press( 'Enter' );
		const select = page.locator( 'select[name="text"]' );
		await expect( select ).toBeFocused();
		// Native select popup keyboard behavior is platform-specific; exercise its change event.
		await select.selectOption( 'button_text' );
		await expect( select ).toHaveValue( 'button_text' );
		expect( await getButtonBindings( page ) ).toEqual( {} );
		await page.keyboard.press( 'Escape' );
		await expect( select ).toHaveCount( 0 );
		await expect( trigger ).toBeFocused();
		await openBindingChooser( page, 'text' );
		await select.selectOption( 'button_text' );
		await editor.canvas
			.getByRole( 'textbox', { name: 'Button text' } )
			.filter( { hasText: 'Second button' } )
			.click();
		await expect( select ).toHaveCount( 0 );
		await expect(
			editor.canvas
				.getByRole( 'textbox', { name: 'Button text' } )
				.filter( { hasText: 'Second button' } )
		).toBeFocused();
		await selectGardenBlock( page, 'core/button' );
		await expect( select ).toHaveCount( 0 );
		expect( await getButtonBindings( page ) ).toEqual( {} );
		await openBindingChooser( page, 'text' );
		await expect( select ).toHaveValue( '' );
		await select.selectOption( 'button_text' );
		await capture( page, 'compact-chooser' );
		await select.press( 'Tab' );
		await expect( page.getByRole( 'button', { name: 'Cancel', exact: true } ) ).toBeFocused();
		await page.keyboard.press( 'Tab' );
		await expect( page.getByRole( 'button', { name: 'Apply', exact: true } ) ).toBeFocused();
		await page.keyboard.press( 'Enter' );
		await expect(
			page.getByRole( 'button', { name: 'Change Button Text binding' } )
		).toBeFocused();
		await expect( page.locator( '[data-binding-target="text"]' ) ).toContainText(
			'Garden button text'
		);
		await bindField( page, 'url', 'button_url' );
		await capture( page, 'compact-bound' );
		const before = await getButtonBindings( page );
		await openBindingChooser( page, 'text' );
		await expect( select ).toHaveValue( 'button_text' );
		await select.selectOption( '' );
		await expect( page.getByRole( 'button', { name: 'Apply', exact: true } ) ).toBeDisabled();
		await page.getByRole( 'button', { name: 'Cancel', exact: true } ).click();
		expect( await getButtonBindings( page ) ).toEqual( before );
		await page.getByRole( 'button', { name: 'Remove Button Text binding' } ).click();
		await expect( trigger ).toBeFocused();
		expect( await getButtonBindings( page ) ).toEqual( { url: before.url } );
	} );

	test( 'opens Conference Event text choices without committing drafts and shows no URL choices', async ( {
		page,
		editor,
	} ) => {
		await insertRemoteDataBlock(
			page,
			editor,
			'remote-data-blocks/conference-event',
			[ { record_id: 'rec2uM1inwuvs9oPz' } ],
			buttonTemplate
		);
		await selectGardenBlock( page, 'core/button' );
		await expect( page.locator( '.rdb-binding-rows select' ) ).toHaveCount( 0 );
		await openBindingChooser( page, 'text' );
		const text = page.locator( 'select[name="text"]' );
		await expect(
			text.locator( 'optgroup[label="Compatible fields"] option[value="title"]' )
		).toHaveText( 'Title' );
		await text.selectOption( 'title' );
		expect( await getButtonBindings( page ) ).toEqual( {} );
		await page.getByRole( 'button', { name: 'Cancel', exact: true } ).click();
		await openBindingChooser( page, 'url' );
		const urlChooser = page.getByRole( 'dialog', { name: 'Bind Button URL', exact: true } );
		await expect( urlChooser.getByRole( 'combobox' ) ).toHaveCount( 0 );
		await expect( urlChooser ).toContainText(
			'No matching fields are available for this attribute.'
		);
		await urlChooser.getByRole( 'button', { name: 'Close', exact: true } ).press( 'Escape' );
		await expect( urlChooser ).toHaveCount( 0 );
		await expect(
			page.getByRole( 'button', { name: 'Bind Button URL', exact: true } )
		).toBeFocused();
		expect( await getButtonBindings( page ) ).toEqual( {} );
	} );

	test( 'preserves legacy and missing selections and sibling args through save, change and removal', async ( {
		page,
		editor,
	} ) => {
		const bindings = {
			linkTarget: { source: 'core/post-meta', args: { key: 'compact-test-unused' } },
			text: {
				source: 'remote-data/binding',
				args: {
					block: 'remote-data-blocks/bindings-garden',
					field: 'title',
					previewIndex: 0,
					label: 'Legacy label',
				},
			},
			url: {
				source: 'remote-data/binding',
				args: { block: 'remote-data-blocks/bindings-garden', field: 'vanished', previewIndex: 0 },
			},
		};
		await insertBindingsGarden( page, editor, [
			{
				name: 'core/buttons',
				innerBlocks: [
					{
						name: 'core/button',
						attributes: {
							text: 'Saved fallback',
							url: 'https://example.org/fallback',
							metadata: { bindings },
						},
					},
				],
			},
		] );
		await selectGardenBlock( page, 'core/button' );
		await expect( page.locator( '[data-binding-target="url"]' ) ).toContainText(
			'Field unavailable'
		);
		await openBindingChooser( page, 'text' );
		await expect( page.locator( 'select[name="text"]' ) ).toHaveValue( 'title' );
		await page.getByRole( 'button', { name: 'Cancel', exact: true } ).click();
		expect( await getButtonBindings( page ) ).toEqual( bindings );
		await editor.publishPost();
		await page.reload();
		await selectGardenBlock( page, 'core/button' );
		expect( await getButtonBindings( page ) ).toEqual( bindings );
		await bindField( page, 'text', 'button_text' );
		expect( await getButtonBindings( page ) ).toEqual( {
			...bindings,
			text: { ...bindings.text, args: { ...bindings.text.args, field: 'button_text' } },
		} );
		await page.getByRole( 'button', { name: 'Remove Button URL binding' } ).click();
		expect( ( await getButtonBindings( page ) ).url ).toBeUndefined();
		expect( ( await getButtonBindings( page ) ).text?.args ).toEqual( {
			...bindings.text.args,
			field: 'button_text',
		} );
	} );
} );
