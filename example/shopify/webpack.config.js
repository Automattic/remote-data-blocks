const { modernize, moduleConfig, scriptConfig } = require( '../../webpack.utils' );

module.exports = [
	modernize( scriptConfig, {
		'filters/shopify-list': './example/shopify/src/filters/shopify-list',
	} ),
	modernize( moduleConfig ),
];
