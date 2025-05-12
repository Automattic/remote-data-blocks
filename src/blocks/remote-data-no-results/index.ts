import { registerBlockType } from '@wordpress/blocks';
import { border, caution } from '@wordpress/icons';

import metadata from './block.json';
import { Edit } from './edit';
import { Save } from './save';
import './style.scss';

registerBlockType< RemoteDataNoResultsBlockAttributes >( metadata.name, {
	edit: Edit,
	icon: {
		src: border,
	},
	save: Save,
	variations: [
		{
			name: 'remote-data-blocks/error',
			title: 'Error',
			icon: { src: caution },
		},
	],
} );
