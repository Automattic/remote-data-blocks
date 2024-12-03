import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import { Edit } from './edit';
import { Save } from './save';
import './style.scss';

/**
 * Every block starts by registering a new block type definition.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */

const { category, name, title } = metadata;

registerBlockType( name, {
	attributes: {
		className: {
			type: 'string',
		},
	},
	category,
	edit: Edit,
	save: Save,
	title,
} );
