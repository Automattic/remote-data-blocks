import { expect, test } from '@wordpress/e2e-test-utils-playwright';

for ( const mode of [ 'page', 'offset', 'cursor' ] ) {
	test( `resets ${ mode } search pagination and retains cross-page selection`, async ( {
		admin,
		editor,
		page,
	} ) => {
		await admin.createNewPost();
		await editor.insertBlock( { name: `remote-data-blocks/pagination-${ mode }` } );
		await editor.canvas.getByRole( 'button', { name: 'Select items', exact: true } ).click();
		const modal = page.getByRole( 'dialog', {
			name: `Pagination ${ mode[ 0 ]!.toUpperCase() }${ mode.slice( 1 ) }`,
		} );
		const search = modal.getByRole( 'searchbox', { name: 'Search' } );
		const row = ( name: RegExp ) => modal.getByRole( 'row', { name } );
		const firstRow = row( /Tree 01/ );
		const pageSize = modal.getByRole( 'combobox', { name: 'Items per page' } );
		async function changePage( direction: 'Next' | 'Previous', expected: RegExp ) {
			await modal.getByRole( 'button', { name: `${ direction } page`, exact: true } ).click();
			await expect( row( expected ) ).toBeVisible();
		}
		await expect( firstRow ).toBeVisible();
		if ( mode === 'cursor' ) {
			const [ searchBox, pageSizeBox ] = await Promise.all( [
				search.boundingBox(),
				pageSize.boundingBox(),
			] );
			expect( searchBox ).not.toBeNull();
			expect( pageSizeBox ).not.toBeNull();
			expect( searchBox!.y + searchBox!.height / 2 ).toBeCloseTo(
				pageSizeBox!.y + pageSizeBox!.height / 2,
				0
			);

			const selected = firstRow.getByRole( 'checkbox' );
			await selected.check();
			await changePage( 'Next', /Tree 11/ );
			await modal.getByRole( 'button', { name: 'Previous page', exact: true } ).click();
			await expect( selected ).toBeChecked();
			await modal.getByRole( 'button', { name: 'Clear selection' } ).click();
		}
		await page.clock.install();
		await changePage( 'Next', /Tree 11/ );

		await search.fill( 'Herb' );
		await page.clock.runFor( 500 );
		await expect( row( /Tree \d/ ) ).toHaveCount( 0 );
		await expect( row( /Herb 02/ ) ).toBeVisible();
		await expect( row( /Herb 22/ ) ).toHaveCount( 0 );
		await search.fill( '' );
		await page.clock.runFor( 500 );
		await expect( row( /Tree 01/ ) ).toBeVisible();
		await changePage( 'Next', /Tree 11/ );
		await pageSize.selectOption( '20' );
		await expect( row( /Tree 01/ ) ).toBeVisible();
		await expect( row( /Herb 20/ ) ).toBeVisible();
		await changePage( 'Next', /Tree 21/ );
		await expect( row( /Herb 40/ ) ).toBeVisible();
		await expect( row( /Tree 11/ ) ).toHaveCount( 0 );

		if ( mode === 'cursor' ) {
			await changePage( 'Next', /Tree 41/ );
			await changePage( 'Previous', /Tree 21/ );
		}

		await changePage( 'Previous', /Tree 01/ );
		await firstRow.getByRole( 'checkbox' ).check();
		await changePage( 'Next', /Tree 21/ );
		await expect( row( /Herb 40/ ) ).toBeVisible();
		await row( /Tree 21/ )
			.getByRole( 'checkbox' )
			.check();
		const submit = modal.getByRole( 'button', { name: 'Select items', exact: true } );
		await submit.press( 'Escape' );
		await expect( modal ).toBeHidden();
		await editor.canvas.getByRole( 'button', { name: 'Select items', exact: true } ).click();
		await expect( submit ).toBeEnabled();
		await submit.click();

		await expect
			.poll( () =>
				page.evaluate( paginationMode => {
					const store = (
						window as unknown as {
							wp: {
								data: {
									select: ( name: string ) => {
										getBlocks: () => { name: string; attributes: { remoteData?: RemoteData } }[];
									};
								};
							};
						}
					 ).wp.data.select( 'core/block-editor' );
					return store
						.getBlocks()
						.find( block => block.name === `remote-data-blocks/pagination-${ paginationMode }` )
						?.attributes.remoteData?.queryInputs.map( input => input.id );
				}, mode )
			)
			.toEqual( [ '1', '21' ] );
	} );
}
