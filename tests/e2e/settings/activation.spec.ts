import { expect, test } from '@wordpress/e2e-test-utils-playwright';

test.describe( 'plugin activation', () => {
	test( 'should have the "Remote Data Blocks" menu item in sidebar', async ( { admin, page } ) => {
		await admin.visitAdminPage( '/' );

		await page.locator( '#menu-settings' ).click();

		const settingsMenuItem = page.locator(
			'.wp-has-current-submenu a[href="options-general.php?page=remote-data-blocks-settings"]'
		);
		await expect( settingsMenuItem ).toBeVisible();
	} );
} );
