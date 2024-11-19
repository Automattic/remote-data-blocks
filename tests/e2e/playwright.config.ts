import { defineConfig } from '@playwright/test';

const baseConfig = require( '@wordpress/scripts/config/playwright.config' );

const config = defineConfig( {
	...baseConfig,
} );

export default config;
