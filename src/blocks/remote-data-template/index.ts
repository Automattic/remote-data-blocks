import { registerBlockType } from '@wordpress/blocks';
import { layout } from '@wordpress/icons';

import metadata from './block.json';
import { Edit } from './edit';
import './filters';
import { Save } from './save';

registerBlockType< RemoteDataTemplateBlockAttributes >( metadata.name, {
	edit: Edit,
	icon: {
		src: layout,
	},
	save: Save,
} );
