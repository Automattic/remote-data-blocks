import { BlockConfiguration } from '@wordpress/blocks';
import { applyFilters } from '@wordpress/hooks';

import {
	REMOTE_DATA_CONTEXT_KEY,
	SUPPORTED_CORE_BLOCKS,
} from '@/blocks/remote-data-container/config/constants';

/**
 * Check if a block supports bindings based on its metadata.
 *
 * @param settings The block configuration settings
 * @returns true if the block supports bindings
 */
function blockSupportsBindings( settings: BlockConfiguration< RemoteDataInnerBlockAttributes > ): boolean {
	// Check if the block has __experimentalLabel defined on any attribute
	if ( settings.attributes ) {
		for ( const attr of Object.values( settings.attributes ) ) {
			// @ts-ignore - __experimentalLabel is not in the types but is supported by WordPress
			if ( attr.__experimentalLabel ) {
				return true;
			}
		}
	}

	// Check if the block has explicit supports.bindings configuration
	// @ts-ignore - bindings is not in the types but is supported by WordPress
	if ( settings.supports?.bindings ) {
		return true;
	}

	return false;
}

export function addUsesContext(
	settings: BlockConfiguration< RemoteDataInnerBlockAttributes >,
	name: string
) {
	/**
	 * Filter the list of supported core blocks for remote data bindings.
	 *
	 * @param supportedBlocks Array of block names that support remote data bindings
	 * @param blockName The name of the block being registered
	 * @param settings The block configuration settings
	 */
	const supportedBlocks = applyFilters(
		'remote_data_blocks_supported_blocks',
		SUPPORTED_CORE_BLOCKS,
		name,
		settings
	) as string[];

	// Check if the block is in the supported list or if it explicitly supports bindings
	if ( ! supportedBlocks.includes( name ) && ! blockSupportsBindings( settings ) ) {
		return settings;
	}

	const { usesContext = [] } = settings;

	if ( ! usesContext?.includes( REMOTE_DATA_CONTEXT_KEY ) ) {
		return {
			...settings,
			usesContext: [ ...usesContext, REMOTE_DATA_CONTEXT_KEY ],
		};
	}

	return settings;
}
