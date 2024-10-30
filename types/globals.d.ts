declare global {
	var REMOTE_DATA_BLOCKS: LocalizedBlockData | undefined;
	var REMOTE_DATA_BLOCKS_SETTINGS: LocalizedSettingsData | undefined;
	var VIP_TRACKS_BASE_PROPS:
		| {
				vipgo_env: string;
				vipgo_org: number;
				is_vip_user: boolean;
				_ui: string; // User ID
				_ut: string; // User Type
		  }
		| undefined;
}

export {};
