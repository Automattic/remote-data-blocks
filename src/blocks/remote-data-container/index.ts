import { registerBlockType, registerBlockVariation } from '@wordpress/blocks';

import { Edit } from '@/blocks/remote-data-container/edit';
import { Save } from '@/blocks/remote-data-container/save';
import { getBlocksConfig } from '@/utils/localized-block-data';

import './style.scss';

// Register a unique block definition for each of the context blocks.
Object.values( getBlocksConfig() ).forEach( blockConfig => {
	registerBlockType< RemoteDataBlockAttributes >( blockConfig.name, {
		...blockConfig.settings,
		attributes: {
			remoteData: {
				type: 'object',
			},
			displayQuery: {
				type: 'string',
			},
		},
		edit: Edit,
		save: Save,
	} );

	Object.entries( blockConfig.variations ).forEach( ( [ queryKey, config ] ) => {
		registerBlockVariation( blockConfig.name, {
			name: config.name,
			title: config.settings?.title ?? config.name,
			attributes: { displayQuery: queryKey },
			isActive: [ 'displayQuery' ],
		} );
	} );
} );
