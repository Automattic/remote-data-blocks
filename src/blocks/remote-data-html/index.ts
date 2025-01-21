import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import edit from './edit';
import save from './save';

registerBlockType( metadata.name, {
	edit,
	save,
} );
