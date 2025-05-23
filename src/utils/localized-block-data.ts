export function getBlockAvailableBindings( blockName: string ): AvailableBindings {
	return getBlockConfig( blockName )?.availableBindings ?? {};
}

export function getBlockConfig( blockName: string ): BlockConfig | undefined {
	return window.REMOTE_DATA_BLOCKS?.config?.[ blockName ];
}

export function getBlockDataSourceType( blockName?: string ): string {
	if ( ! blockName ) {
		return '';
	}

	return getBlockConfig( blockName )?.dataSourceType ?? '';
}

/**
 * Get the title of a remote data block.
 *
 * The title could be set in the block config or it could be the block name, without the remote-data-blocks/ prefix.
 * If the title is not found, or the block name is not found, then unknown is returned.
 *
 * @param blockName The name of the remote data block.
 * @returns The title of the remote data block.
 */
export function getBlockTitle( blockName?: string ): string {
	if ( ! blockName ) {
		return '';
	}

	return (
		getBlockConfig( blockName )?.settings?.title ?? blockName.replace( 'remote-data-blocks/', '' )
	);
}

export function getBlocksConfig(): BlocksConfig {
	return window.REMOTE_DATA_BLOCKS?.config ?? {};
}

export function getRestUrl(): string {
	return window.REMOTE_DATA_BLOCKS?.rest_url ?? 'http://127.0.0.1:9999';
}

/**
 * Return global `Tracks` properties to be sent with every event.
 */
export function getTracksGlobalProperties(): TracksGlobalProperties | undefined {
	return window.REMOTE_DATA_BLOCKS?.tracks_global_properties;
}
