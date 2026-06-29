/* eslint-disable import/no-extraneous-dependencies */
const AutomatticPlugin = require( '@automattic/eslint-plugin-wpvip' );
const ReactHooks = require( 'eslint-plugin-react-hooks' );
const tseslint = require( 'typescript-eslint' );

module.exports = [
	{
		ignores: [
			'build/**',
			'example/templates/airtable-map-block/src/**',
			'node_modules/**',
			'vendor/**',
			'**/*.php',
		],
	},
	...AutomatticPlugin.configs.recommended,
	...AutomatticPlugin.configs[ 'weak-javascript' ],
	...AutomatticPlugin.configs[ 'weak-typescript' ],
	{
		plugins: {
			'@typescript-eslint': tseslint.plugin,
			'react-hooks': ReactHooks,
		},
		languageOptions: {
			globals: {
				REMOTE_DATA_BLOCKS: 'readonly',
				REMOTE_DATA_BLOCKS_SETTINGS: 'readonly',
			},
		},
		rules: {
			'@typescript-eslint/no-unnecessary-type-assertion': 'warn',
			complexity: 'warn',
			'react-hooks/immutability': 'warn',
		},
	},
];
