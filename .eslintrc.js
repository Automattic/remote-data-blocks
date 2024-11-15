require( '@automattic/eslint-plugin-wpvip/init' );

module.exports = {
	extends: [ 'plugin:@automattic/wpvip/recommended' ],
	globals: {
		REMOTE_DATA_BLOCKS: 'readonly',
		REMOTE_DATA_BLOCKS_SETTINGS: 'readonly',
	},
};
