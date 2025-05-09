import { registerBlockType } from '@wordpress/blocks';

import { Edit } from '@/blocks/remote-data-container/edit';
import { Save } from '@/blocks/remote-data-container/save';
import { getBlocksConfig } from '@/utils/localized-block-data';
import { SourceTypeIcon } from './SourceTypeIcon';

import './style.scss';

// Register a unique block definition for each of the context blocks.
Object.values( getBlocksConfig() ).forEach( blockConfig => {
	registerBlockType< RemoteDataBlockAttributes >( blockConfig.name, {
		...blockConfig.settings,
		attributes: {
			remoteData: {
				type: 'object',
			},
		},
		icon: {
			// eslint-disable-next-line @typescript-eslint/no-unsafe-assignment
			src: SourceTypeIcon,
		},
		edit: Edit,
		save: Save,
	} );
} );
