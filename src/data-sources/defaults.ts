export function getAutoRegisterBlocksDefault(): boolean {
	return window.REMOTE_DATA_BLOCKS_SETTINGS?.auto_register_blocks_default ?? true;
}
