const { modernize, moduleConfig, scriptConfig } = require( '../../webpack.utils' );

module.exports = [ modernize( scriptConfig ), modernize( moduleConfig ) ];
