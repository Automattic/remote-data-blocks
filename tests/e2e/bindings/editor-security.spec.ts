import { expect, test } from '@wordpress/e2e-test-utils-playwright';

test( 'isolates Remote HTML scripts from the editor and resizes embedded content', async ( {
	admin,
	editor,
} ) => {
	await admin.createNewPost();
	await editor.insertBlock( {
		name: 'remote-data-blocks/remote-html',
		attributes: {
			content:
				'<p style="height:320px">Embedded preview</p><script>document.body.dataset.embed = "ready";</script>',
		},
	} );
	const frame = editor.canvas.locator( 'iframe.components-sandbox' );
	await expect( frame ).toHaveAttribute( 'sandbox', 'allow-scripts allow-presentation' );
	await expect( frame.contentFrame().locator( 'body' ) ).toHaveAttribute( 'data-embed', 'ready' );
	await expect
		.poll( async () => Number( await frame.getAttribute( 'height' ) ) )
		.toBeGreaterThan( 300 );
	const isolated = await frame
		.contentFrame()
		.locator( 'body' )
		.evaluate( () => {
			try {
				return window.parent.document === undefined;
			} catch {
				return true;
			}
		} );
	expect( isolated ).toBe( true );
} );

test( 'keeps the Remote HTML preview stable with viewport-relative content', async ( {
	admin,
	editor,
} ) => {
	await admin.createNewPost();
	await editor.insertBlock( {
		name: 'remote-data-blocks/remote-html',
		attributes: {
			content: '<div style="height:100vh;box-sizing:content-box;padding-top:32px">Preview</div>',
		},
	} );
	const frame = editor.canvas.locator( 'iframe.components-sandbox' );
	await expect( frame.contentFrame().getByText( 'Preview' ) ).toBeVisible();
	await expect( frame ).toHaveAttribute( 'height', '182' );
	const heights = await frame.evaluate(
		element =>
			new Promise< ( string | null )[] >( resolve => {
				const samples: ( string | null )[] = [];
				function sampleHeight() {
					samples.push( element.getAttribute( 'height' ) );
					if ( samples.length === 12 ) {
						resolve( samples );
						return;
					}
					requestAnimationFrame( sampleHeight );
				}
				sampleHeight();
			} )
	);
	const finalHeight = heights[ heights.length - 1 ];
	expect( finalHeight ).toBe( '182' );
	expect( heights.slice( -6 ) ).toEqual( Array( 6 ).fill( finalHeight ) );
} );

test( 'preserves formatting and metadata when reopening saved inline bindings', async ( {
	admin,
	editor,
	page,
} ) => {
	await admin.createNewPost();
	await editor.insertBlock( {
		name: 'core/paragraph',
		attributes: {
			content:
				'Before <remote-data-blocks-inline-field data-query="{}"><em>Saved inline</em><iframe srcdoc="inert"></iframe></remote-data-blocks-inline-field> after',
		},
	} );
	const field = editor.canvas.locator( 'remote-data-blocks-inline-field' );
	await expect( field ).toContainText( 'Saved inline' );
	await expect( field.locator( 'em' ) ).toHaveText( 'Saved inline' );
	await expect( field.locator( 'iframe' ) ).toHaveCount( 0 );
	await expect( field ).toHaveAttribute( 'data-query', '{}' );
	await editor.saveDraft();
	await page.reload();
	await expect( editor.canvas.locator( 'remote-data-blocks-inline-field em' ) ).toHaveText(
		'Saved inline'
	);
	await expect( editor.canvas.locator( 'remote-data-blocks-inline-field iframe' ) ).toHaveCount(
		0
	);
} );
