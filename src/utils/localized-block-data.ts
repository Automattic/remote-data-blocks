export function getBlockAvailableBindings( blockName: string ): AvailableBindings {
	return getBlockConfig( blockName )?.availableBindings ?? {};
}

export function getBlockConfig( blockName: string ): BlockConfig | undefined {
	return window.REMOTE_DATA_BLOCKS?.config?.[ blockName ];
}

export function getBlockDataSource( blockName: string ): string {
	return getBlockConfig( blockName )?.dataSource ?? '';
}

export function getBlocksConfig(): BlocksConfig {
	return window.REMOTE_DATA_BLOCKS?.config ?? {};
}

export function getRestUrl(): string {
	return window.REMOTE_DATA_BLOCKS?.rest_url ?? 'http://127.0.0.1:9999';
}

export function getTracksBaseProps(): Record< string, unknown > {
	return window.REMOTE_DATA_BLOCKS?.track_base_props ?? {};
}
