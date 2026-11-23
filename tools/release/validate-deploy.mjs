import { readFileSync } from 'node:fs';

import { parseVersion } from './version.mjs';

try {
	const [ version ] = process.argv.slice( 2 );
	if ( process.argv.length !== 3 || parseVersion( version ).channel ) {
		throw new Error( 'Only stable versions can be deployed to WordPress.org.' );
	}
	const php = readFileSync( 'remote-data-blocks.php', 'utf8' );
	const readme = readFileSync( 'readme.txt', 'utf8' );
	const header = php.match( /^ \* Version: (.+)$/m )?.[ 1 ];
	const constant = php.match(
		/^define\( 'REMOTE_DATA_BLOCKS__PLUGIN_VERSION', '([^']+)' \);$/m
	)?.[ 1 ];
	const stableTag = readme.match( /^Stable tag: (.+)$/m )?.[ 1 ];
	if ( [ header, constant, stableTag ].some( value => value !== version ) ) {
		throw new Error(
			'Deploy version must match the checked-out plugin header, version constant, and Stable tag.'
		);
	}
} catch ( error ) {
	console.error( error.message );
	process.exitCode = 1;
}
