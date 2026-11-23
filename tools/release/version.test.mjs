import assert from 'node:assert/strict';
import { execFileSync, spawnSync } from 'node:child_process';
import { cpSync, mkdirSync, mkdtempSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

import { nextVersion, parseVersion } from './version.mjs';

const root = path.resolve( path.dirname( fileURLToPath( import.meta.url ) ), '../..' );

for ( const [ current, type, channel, expected ] of [
	[ '0.0.5', 'patch', 'stable', '0.0.6' ],
	[ '0.0.5', 'minor', 'stable', '0.1.0' ],
	[ '0.0.5', 'major', 'stable', '1.0.0' ],
	[ '0.0.5', 'patch', 'alpha', '0.0.6-alpha.1' ],
	[ '0.0.5', 'minor', 'beta', '0.1.0-beta.1' ],
	[ '0.0.5', 'major', 'beta', '1.0.0-beta.1' ],
	[ '1.0.0-alpha.1', 'none', 'alpha', '1.0.0-alpha.2' ],
	[ '1.0.0-alpha.2', 'none', 'beta', '1.0.0-beta.1' ],
	[ '1.0.0-beta.1', 'none', 'beta', '1.0.0-beta.2' ],
	[ '1.0.0-beta.9', 'none', 'beta', '1.0.0-beta.10' ],
	[ '1.0.0-beta.2', 'none', 'stable', '1.0.0' ],
	[ '1.0.0-alpha.2', 'none', 'stable', '1.0.0' ],
	[ '1.0.0-beta.2', 'patch', 'beta', '1.0.1-beta.1' ],
	[ '1.0.0-beta.2', 'minor', 'beta', '1.1.0-beta.1' ],
	[ '1.0.0-beta.2', 'major', 'beta', '2.0.0-beta.1' ],
	[ '1.0.0-beta.2', 'minor', 'alpha', '1.1.0-alpha.1' ],
	[ '1.2.3-beta.2', 'major', 'stable', '2.0.0' ],
] ) {
	test( `${ current } + ${ type } + ${ channel } = ${ expected }`, () => {
		assert.equal( nextVersion( current, type, channel ), expected );
	} );
}

test( 'rejects duplicate versions and semantic downgrades', () => {
	for ( const [ current, channel ] of [
		[ '1.0.0-beta.2', 'alpha' ],
		[ '1.0.0', 'alpha' ],
		[ '1.0.0', 'beta' ],
		[ '1.0.0', 'stable' ],
	] ) {
		assert.throws( () => nextVersion( current, 'none', channel ), /must be newer/ );
	}
} );

test( 'rejects malformed versions and unsupported release types', () => {
	for ( const version of [
		'',
		'v1.0.0',
		'1.0',
		'01.0.0',
		'1.0.0-beta',
		'1.0.0-beta.0',
		'1.0.0-beta.01',
		'1.0.0-rc.1',
		'1.0.0; echo bad',
	] ) {
		assert.throws( () => parseVersion( version ), /Invalid release version/ );
	}
	assert.throws( () => nextVersion( '1.0.0', 'unknown' ), /Invalid release type/ );
	assert.throws( () => nextVersion( '1.0.0', 'major', 'rc' ), /Invalid release channel/ );
	assert.equal( nextVersion( '0.0.5', 'patch' ), '0.0.6' );
} );

test( 'the release workflow classifies stable, alpha, and beta versions', () => {
	for ( const [ version, expected ] of [
		[ '1.0.0', 'false' ],
		[ '1.0.0-alpha.1', 'true' ],
		[ '1.0.0-beta.2', 'true' ],
	] ) {
		assert.equal(
			execFileSync( process.execPath, [ 'tools/release/version.mjs', '--prerelease', version ], {
				cwd: root,
				encoding: 'utf8',
			} ).trim(),
			expected
		);
	}
	assert.notEqual(
		spawnSync( process.execPath, [ 'tools/release/version.mjs', '--prerelease', 'bad' ], {
			cwd: root,
		} ).status,
		0
	);
} );

function fixture( t, version = '0.0.5' ) {
	const directory = mkdtempSync( path.join( tmpdir(), 'rdb-release-' ) );
	t.after( () => rmSync( directory, { recursive: true, force: true } ) );
	mkdirSync( path.join( directory, 'bin' ) );
	mkdirSync( path.join( directory, 'tools/release' ), { recursive: true } );
	for ( const file of [
		'bin/release',
		'tools/release/version.mjs',
		'tools/release/validate-deploy.mjs',
	] ) {
		cpSync( path.join( root, file ), path.join( directory, file ) );
	}
	writeFileSync(
		path.join( directory, 'remote-data-blocks.php' ),
		`<?php\n/**\n * Version: ${ version }\n */\ndefine( 'REMOTE_DATA_BLOCKS__PLUGIN_VERSION', '${ version }' );\n`
	);
	writeFileSync( path.join( directory, 'readme.txt' ), 'Stable tag: 0.0.5\n' );
	const run = ( command, args ) =>
		execFileSync( command, args, {
			cwd: directory,
			encoding: 'utf8',
			env: {
				...process.env,
				GIT_CONFIG_GLOBAL: '/dev/null',
				GIT_CONFIG_NOSYSTEM: '1',
				HUSKY: '0',
			},
			stdio: [ 'ignore', 'pipe', 'pipe' ],
		} ).trim();
	run( 'git', [ 'init' ] );
	run( 'git', [ 'config', 'user.name', 'Release Test' ] );
	run( 'git', [ 'config', 'user.email', 'release@example.com' ] );
	run( 'git', [ 'add', '.' ] );
	run( 'git', [ 'commit', '-m', 'Initial version' ] );
	return { directory, run };
}

test( 'the release script prepares alpha and beta releases and promotes to stable', t => {
	const { directory, run } = fixture( t );
	for ( const [ type, channel, version, stableTag ] of [
		[ 'major', 'alpha', '1.0.0-alpha.1', '0.0.5' ],
		[ 'none', 'alpha', '1.0.0-alpha.2', '0.0.5' ],
		[ 'none', 'beta', '1.0.0-beta.1', '0.0.5' ],
		[ 'none', 'beta', '1.0.0-beta.2', '0.0.5' ],
		[ 'none', 'stable', '1.0.0', '1.0.0' ],
		[ 'patch', 'stable', '1.0.1', '1.0.1' ],
	] ) {
		run( 'sh', [ 'bin/release', type, channel ] );
		const php = readFileSync( path.join( directory, 'remote-data-blocks.php' ), 'utf8' );
		assert.ok( php.includes( `Version: ${ version }\n` ) );
		assert.ok( php.includes( `define( 'REMOTE_DATA_BLOCKS__PLUGIN_VERSION', '${ version }' );` ) );
		assert.equal(
			readFileSync( path.join( directory, 'readme.txt' ), 'utf8' ),
			`Stable tag: ${ stableTag }\n`
		);
		assert.equal( run( 'git', [ 'branch', '--show-current' ] ), `release/v${ version }` );
		assert.equal( run( 'git', [ 'log', '-1', '--format=%s' ] ), `v${ version }` );
		assert.equal( run( 'git', [ 'status', '--porcelain' ] ), '' );
	}
	const head = run( 'git', [ 'rev-parse', 'HEAD' ] );
	assert.throws( () => run( 'sh', [ 'bin/release', 'none', 'beta' ] ) );
	assert.equal( run( 'git', [ 'rev-parse', 'HEAD' ] ), head );
	assert.equal( run( 'git', [ 'status', '--porcelain' ] ), '' );
} );

test( 'release defaults to stable and rejects branch collisions before editing files', t => {
	const { run } = fixture( t );
	run( 'git', [ 'branch', 'release/v0.0.6' ] );
	assert.throws( () => run( 'sh', [ 'bin/release', 'patch' ] ) );
	assert.equal( run( 'git', [ 'status', '--porcelain' ] ), '' );
	run( 'sh', [ 'bin/release', 'minor' ] );
	assert.equal( run( 'git', [ 'branch', '--show-current' ] ), 'release/v0.1.0' );
} );

test( 'release rejects dirty worktrees and inconsistent plugin versions', t => {
	const { directory, run } = fixture( t );
	const phpFile = path.join( directory, 'remote-data-blocks.php' );
	writeFileSync( phpFile, readFileSync( phpFile, 'utf8' ).replace( "'0.0.5'", "'0.0.4'" ) );
	const before = readFileSync( phpFile, 'utf8' );
	assert.throws( () => run( 'sh', [ 'bin/release', 'patch' ] ) );
	assert.equal( readFileSync( phpFile, 'utf8' ), before );
	run( 'git', [ 'add', '.' ] );
	run( 'git', [ 'commit', '-m', 'Inconsistent versions' ] );
	assert.throws( () => run( 'sh', [ 'bin/release', 'patch' ] ) );
	assert.equal( run( 'git', [ 'status', '--porcelain' ] ), '' );
} );

test( 'WordPress.org deploy accepts only matching stable versions, including manual dispatch', t => {
	const { directory, run } = fixture( t );
	run( process.execPath, [ 'tools/release/validate-deploy.mjs', '0.0.5' ] );
	for ( const version of [ '0.0.6', '0.0.6-alpha.1', '0.0.6-beta.2', 'bad' ] ) {
		assert.throws( () =>
			run( process.execPath, [ 'tools/release/validate-deploy.mjs', version ] )
		);
	}
	// A stable-looking manual input must not deploy prerelease source.
	run( 'sh', [ 'bin/release', 'patch', 'beta' ] );
	assert.throws( () => run( process.execPath, [ 'tools/release/validate-deploy.mjs', '0.0.5' ] ) );
	assert.throws( () => run( process.execPath, [ 'tools/release/validate-deploy.mjs', '0.0.6' ] ) );
	run( 'sh', [ 'bin/release', 'none', 'stable' ] );
	run( process.execPath, [ 'tools/release/validate-deploy.mjs', '0.0.6' ] );
	writeFileSync( path.join( directory, 'readme.txt' ), 'Stable tag: 0.0.5\n' );
	assert.throws( () => run( process.execPath, [ 'tools/release/validate-deploy.mjs', '0.0.6' ] ) );
} );
