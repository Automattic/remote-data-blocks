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
