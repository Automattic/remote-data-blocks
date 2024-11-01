declare global {
	var REMOTE_DATA_BLOCKS: LocalizedBlockData | undefined;
	var REMOTE_DATA_BLOCKS_SETTINGS: LocalizedSettingsData | undefined;

	/**
	 * Generic Tracks properties sent from MU Plugins.
	 */
	var VIP_TRACKS_BASE_PROPS: VipTracksBaseProps | undefined;
}

export interface VipTracksBaseProps {
	hosting_provider: string;
	is_vip_user: boolean;
	is_multisite: boolean;
	vipgo_env: string;
	vipgo_org: number;
	wp_version: string;
	_ui: string; // User ID
	_ut: string; // User Type
}

export {};
