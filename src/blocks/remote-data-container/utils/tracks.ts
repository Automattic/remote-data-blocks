import { recordTracksEvent } from '@automattic/calypso-analytics';

export function sendTracksEvent( eventName: string, props: object ): void {
	const baseProps = window.wpvipTracksBaseProps;

	// Do not track if the base props are not available i.e. user is not on VIP platform.
	if ( ! baseProps ) {
		return;
	}

	// if ( baseProps.vipgo_env === 'local' ) {
	// 	return;
	// }

	recordTracksEvent( eventName, {
		...window.wpvipTracksBaseProps,
		...props,
	} );
}
