export function getBlockAvailableBindings( blockName: string ): AvailableBindings {
	return getBlockConfig( blockName )?.availableBindings ?? {};
}

export function getBlockConfig( blockName: string ): BlockConfig | undefined {
	return window.REMOTE_DATA_BLOCKS?.config?.[ blockName ];
}

export function getBlocksConfig(): BlocksConfig {
	return window.REMOTE_DATA_BLOCKS?.config ?? {};
}

export function getRestUrl(): string {
	return window.REMOTE_DATA_BLOCKS?.rest_url ?? 'http://127.0.0.1:9999';
}
