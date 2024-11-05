declare global {
	var REMOTE_DATA_BLOCKS: LocalizedBlockData | undefined;
	var REMOTE_DATA_BLOCKS_SETTINGS: LocalizedSettingsData | undefined;

	/**
	 * Generic Tracks properties sent from MU Plugins.
	 */
	var VIP_TRACKS_BASE_PROPS: VipTracksBaseProps | undefined;
}

export {};
