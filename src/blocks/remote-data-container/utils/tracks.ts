import { recordTracksEvent } from '@automattic/calypso-analytics';

import { getTracksBaseProps } from '@/utils/localized-block-data';

interface TRACKS_EVENTS {
	remotedatablocks_remote_data_container_actions: {
		action: string;
		block_target_attribute?: string;
		data_source_type: string;
		remote_data_field?: string;
	};
	remotedatablocks_field_shortcode: {
		action: string;
		data_source_type?: string;
		selection_path?: string;
	};
	remotedatablocks_add_block: {
		action: string;
		selected_option: string;
		data_source_type: string;
	};
	remotedatablocks_remote_data_container_override: {
		data_source_type: string;
		override_type?: string;
		override_target?: string;
	};
	remotedatablocks_associate_block_type_to_pattern: {
		data_source_type: string;
		is_pattern_synced: boolean;
	};
}

/**
 * Send a tracks event with the given name and properties.
 */
export function sendTracksEvent< K extends keyof TRACKS_EVENTS >(
	eventName: K,
	props: TRACKS_EVENTS[ K ]
): void {
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
