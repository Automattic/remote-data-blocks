import { recordTracksEvent } from '@automattic/calypso-analytics';

export function sendTracksEvent( eventName: string, props: object ): void {
	// Do not track if the base props are not available i.e. user is not on VIP platform.
	if ( ! window.wpvipTracksBaseProps ) {
		return;
	}

	recordTracksEvent( eventName, {
		...window.wpvipTracksBaseProps,
		...props,
	} );
}
