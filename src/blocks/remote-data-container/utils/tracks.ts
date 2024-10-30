import { recordTracksEvent } from '@automattic/calypso-analytics';

import { getTracksBaseProps } from '@/utils/localized-block-data';

/**
 * Send a tracks event with the given name and properties.
 */
export function sendTracksEvent( eventName: string, props: Record< string, unknown > ): void {
	const vipBaseProps = window.VIP_TRACKS_BASE_PROPS;

	// Do not track if the base props are not available i.e. user is not on VIP platform.
	if ( ! vipBaseProps ) {
		return;
	}

	// Do not track on local environments.
	if ( vipBaseProps.vipgo_env === 'local' ) {
		return;
	}

	recordTracksEvent( eventName, {
		...window.VIP_TRACKS_BASE_PROPS,
		...getTracksBaseProps(),
		...props,
	} );
}
