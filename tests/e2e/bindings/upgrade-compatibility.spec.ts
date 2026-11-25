import { expect, test } from '@wordpress/e2e-test-utils-playwright';

import fixtures from '../../fixtures/compatibility/saved-remote-data.json';

for ( const fixture of fixtures.filter( item => ! item.clientOnly ) ) {
	test( `upgrades a saved post with ${ fixture.name }`, async ( {
		admin,
		editor,
		page,
		requestUtils,
	} ) => {
		const binding = {
			source: 'remote-data/binding',
			args: { block: fixture.remoteData.blockName, field: 'title' },
		};
		const content = `<!-- wp:${ fixture.remoteData.blockName } ${ JSON.stringify( {
			remoteData: fixture.remoteData,
		} ) } --><div class="wp-block-${ fixture.remoteData.blockName.replace( '/', '-' ) } rdb-container"><!-- wp:paragraph ${ JSON.stringify( {
			metadata: { name: 'Historical title', bindings: { content: binding } },
		} ) } --><p>Saved garden</p><!-- /wp:paragraph --></div><!-- /wp:${
			fixture.remoteData.blockName
		} -->`;
		const post = await requestUtils.createPost( {
			title: 'Historical upgrade',
			content,
			status: 'draft',
			date_gmt: '2025-01-01T00:00:00',
		} );
		await page.goto( `/?p=${ post.id }&preview=true` );
		await expect( page.locator( '.entry-content' ) ).toContainText( 'Local garden collection' );
		await admin.editPost( post.id );
		await expect( editor.canvas.locator( '.block-editor-warning' ) ).toHaveCount( 0 );
		await expect(
			editor.canvas.locator( 'p' ).filter( { hasText: 'Local garden collection' } )
		).toHaveCount( 1 );
		await editor.canvas.getByRole( 'textbox', { name: 'Add title' } ).fill( 'Saved historical upgrade' );
		await editor.saveDraft();
		await page.reload();
		await expect(
			editor.canvas.locator( 'p' ).filter( { hasText: 'Local garden collection' } )
		).toHaveCount( 1 );
		expect( await editor.getEditedPostContent() ).toContain( 'Historical title' );
		await page.goto( `/?p=${ post.id }&preview=true` );
		await expect( page.locator( '.entry-content' ) ).toContainText( 'Local garden collection' );
	} );
}
