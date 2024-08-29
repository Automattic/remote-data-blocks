import { defineConfig } from 'vitest/config';
import path from 'node:path';

export default defineConfig( {
	resolve: {
		alias: {
			'@': path.resolve( __dirname, 'src/' ),
		},
	},
	test: {
		environment: 'happy-dom',
		exclude: [ '**/build/**', '**/node_modules/**', '**/vendor/**' ],
		setupFiles: [ './tests/vitest.setup.ts' ],
	},
} );
