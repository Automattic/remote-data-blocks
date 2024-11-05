import { recordTracksEvent } from '@automattic/calypso-analytics';
import { describe, expect, it, vi, beforeEach } from 'vitest';

import { sendTracksEvent } from '@/blocks/remote-data-container/utils/tracks';
import { getTracksBaseProps } from '@/utils/localized-block-data';

vi.mock( '@automattic/calypso-analytics', () => ( {
	recordTracksEvent: vi.fn(),
} ) );

vi.mock( '@/utils/localized-block-data', () => ( {
	getTracksBaseProps: vi.fn(),
} ) );

describe( 'sendTracksEvent', () => {
	const defaultTrackProps: VipTracksBaseProps = {
		vipgo_env: 'local',
		vipgo_org: 1,
		is_vip_user: false,
		hosting_provider: 'vip',
		is_multisite: false,
		wp_version: '6.6',
		_ui: '1', // User ID
		_ut: 'anon', // User Type
	};

	beforeEach( () => {
		window.VIP_TRACKS_BASE_PROPS = defaultTrackProps;
		vi.clearAllMocks();
	} );

	it( 'should not record event if VIP_TRACKS_BASE_PROPS is not defined', () => {
		window.VIP_TRACKS_BASE_PROPS = undefined;

		sendTracksEvent( 'remotedatablocks_field_shortcode', { action: 'value' } );

		expect( recordTracksEvent ).not.toHaveBeenCalled();
	} );

	it( 'should not track if vipgo_env is local', () => {
		sendTracksEvent( 'remotedatablocks_field_shortcode', { action: 'value' } );

		expect( recordTracksEvent ).not.toHaveBeenCalled();
	} );

	it( 'should call recordTracksEvent with the correct event name and merged properties', () => {
		if ( window.VIP_TRACKS_BASE_PROPS ) {
			window.VIP_TRACKS_BASE_PROPS.vipgo_env = 'production';
		}
		vi.mocked( getTracksBaseProps ).mockReturnValue( { plugin_version: '1.0' } );
		sendTracksEvent( 'remotedatablocks_field_shortcode', { action: 'actionName' } );

		expect( recordTracksEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordTracksEvent ).toHaveBeenCalledWith( 'remotedatablocks_field_shortcode', {
			...defaultTrackProps,
			plugin_version: '1.0',
			vipgo_env: 'production',
			action: 'actionName',
		} );
	} );
} );
