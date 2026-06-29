import path from 'node:path';
import { defineConfig } from 'vitest/config';

export default defineConfig( {
	resolve: {
		alias: [
			{ find: '@', replacement: path.resolve( __dirname, 'src/' ) },
			{
				find: /^@wordpress\/blocks$/,
				replacement: path.resolve( __dirname, 'node_modules/@wordpress/blocks/build/index.cjs' ),
			},
		],
	},
	test: {
		environment: 'happy-dom',
		exclude: [ '**/build/**', '**/node_modules/**', '**/vendor/**', '**/tests/e2e/**' ],
		setupFiles: [ './tests/src/vitest.setup.ts' ],
		coverage: {
			reporter: 'clover',
			reportsDirectory: './coverage/vitest',
		},
	},
} );
