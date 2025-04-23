import { registerBlockType } from '@wordpress/blocks';
import { error } from '@wordpress/icons';

import metadata from './block.json';
import { Edit } from './edit';
import { Save } from './save';
import './style.scss';

registerBlockType< RemoteDataEmptyResultBlockAttributes >( metadata.name, {
	edit: Edit,
	icon: {
		src: error,
	},
	save: Save,
} );
