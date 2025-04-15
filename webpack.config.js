const ForkTsCheckerWebpackPlugin = require( 'fork-ts-checker-webpack-plugin' );

const additionalScripts = {
	'block-editor/index': './src/block-editor/index',
	'dataviews/index': './src/dataviews/index',
	'pattern-editor/index': './src/pattern-editor/index',
	'settings/index': './src/settings/index',
};

const { modernize, moduleConfig, scriptConfig } = require( './webpack.utils' );

// Add watchOptions configuration to reduce file watching load
const watchOptions = {
	ignored: /node_modules/,
	aggregateTimeout: 300,
};

module.exports = [
	modernize(
		scriptConfig,
		additionalScripts,
		[
			// we only need to fork one copy of ts-checker off here in these webpack exports
			new ForkTsCheckerWebpackPlugin(),
		],
		watchOptions
	),
	modernize( moduleConfig, {}, [], watchOptions ),
];
