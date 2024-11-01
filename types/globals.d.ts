import { LocalizedBlockData } from './localized-block-data';
import { LocalizedSettingsData } from './localized-settings';

declare global {
	var REMOTE_DATA_BLOCKS: LocalizedBlockData | undefined;
	var REMOTE_DATA_BLOCKS_SETTINGS: LocalizedSettingsData | undefined;
	var VIP_TRACKS_BASE_PROPS: VipTracksBaseProps | undefined;
}

export interface VipTracksBaseProps {
	vipgo_env: string;
	vipgo_org: number;
	is_vip_user: boolean;
	_ui: string; // User ID
	_ut: string; // User Type
}

export {};
