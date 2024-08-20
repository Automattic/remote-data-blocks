import { defineConfig } from 'vitest/config';

export default defineConfig( {
	test: {
		environment: 'happy-dom',
		setupFiles: [ './tests/vitest.setup.ts' ],
		exclude: [ '**/build/**', '**/node_modules/**', '**/vendor/**' ],
	},
} );
