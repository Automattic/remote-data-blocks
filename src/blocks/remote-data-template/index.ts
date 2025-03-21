import { registerBlockType } from '@wordpress/blocks';
import { post } from '@wordpress/icons';

import metadata from './block.json';
import { Edit } from './edit';
import { Save } from './save';
import './filters';

registerBlockType< RemoteDataTemplateBlockAttributes >( metadata.name, {
	edit: Edit,
	icon: {
		src: post,
	},
	save: Save,
} );
