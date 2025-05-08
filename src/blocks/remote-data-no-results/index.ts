import { registerBlockType } from '@wordpress/blocks';
import { border, caution } from '@wordpress/icons';

import metadata from './block.json';
import { Edit } from './edit';
import { Save } from './save';
import './style.scss';

// @ts-expect-error - WordPress registerBlockType type definition does not include variations even though this is the right way to do it.
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
