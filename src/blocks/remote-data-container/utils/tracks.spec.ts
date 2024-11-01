import { recordTracksEvent } from '@automattic/calypso-analytics';
import { describe, expect, it, vi, beforeEach } from 'vitest';

import { sendTracksEvent } from '@/blocks/remote-data-container/utils/tracks';

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
		_ui: '1', // User ID
		_ut: 'anon', // User Type
	};

	beforeEach( () => {
		window.VIP_TRACKS_BASE_PROPS = defaultTrackProps;
		vi.clearAllMocks();
	} );

	it( 'should not record event if VIP_TRACKS_BASE_PROPS is not defined', () => {
		window.VIP_TRACKS_BASE_PROPS = undefined;

		sendTracksEvent( 'test_event', { key: 'value' } );

		expect( recordTracksEvent ).not.toHaveBeenCalled();
	} );

	it( 'should not track if vipgo_env is local', () => {
		sendTracksEvent( 'test_event', { key: 'value' } );

		expect( recordTracksEvent ).not.toHaveBeenCalled();
	} );

	it( 'should call recordTracksEvent with the correct event name and merged properties', () => {
		if ( window.VIP_TRACKS_BASE_PROPS ) {
			window.VIP_TRACKS_BASE_PROPS.vipgo_env = 'production';
		}

		sendTracksEvent( 'test_event', { customProp: 'customValue' } );

		expect( recordTracksEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordTracksEvent ).toHaveBeenCalledWith( 'test_event', {
			...defaultTrackProps,
			vipgo_env: 'production',
			customProp: 'customValue',
		} );
	} );
} );
