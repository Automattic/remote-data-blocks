import { afterEach, describe, expect, it } from 'vitest';

import { getTracksGlobalProperties } from '@/utils/localized-block-data';

const properties: TracksGlobalProperties = {
	plugin_version: '1.0.0',
	hosting_provider: 'wpvip',
	is_vip_user: false,
	is_multisite: false,
	vip_env: 'production',
	vip_org: 1,
	wp_version: '6.9',
	_ui: 'abc',
	_ut: 'vip:user_email',
};

describe( 'getTracksGlobalProperties', () => {
	afterEach( () => {
		window.REMOTE_DATA_BLOCKS = undefined;
		window.REMOTE_DATA_BLOCKS_SETTINGS = undefined;
	} );

	it( 'reads the block editor localized properties', () => {
		window.REMOTE_DATA_BLOCKS = { config: {}, rest_url: '', tracks_global_properties: properties };

		expect( getTracksGlobalProperties() ).toBe( properties );
	} );

	it( 'reads the settings page localized properties', () => {
		window.REMOTE_DATA_BLOCKS_SETTINGS = {
			auto_register_blocks_default: true,
			branch: '',
			hash: '',
			version: '1.0.0',
			tracks_global_properties: properties,
		};

		expect( getTracksGlobalProperties() ).toBe( properties );
	} );

	it( 'returns undefined where PHP localized null (off VIP)', () => {
		// PHP localizes null off VIP, which the types leave out.
		// @ts-expect-error -- null is what arrives at runtime.
		window.REMOTE_DATA_BLOCKS = { config: {}, rest_url: '', tracks_global_properties: null };
		window.REMOTE_DATA_BLOCKS_SETTINGS = {
			auto_register_blocks_default: true,
			branch: '',
			hash: '',
			version: '1.0.0',
			// @ts-expect-error -- null is what arrives at runtime.
			tracks_global_properties: null,
		};

		expect( getTracksGlobalProperties() ).toBeUndefined();
	} );
} );
