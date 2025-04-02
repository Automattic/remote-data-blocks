import { registerBlockType } from '@wordpress/blocks';
import { queryPagination } from '@wordpress/icons';

import metadata from './block.json';
import { Edit } from './edit';
import { Save } from './save';
import './style.scss';

registerBlockType< RemoteDataPaginationBlockAttributes >( metadata.name, {
	edit: Edit,
	icon: {
		src: queryPagination,
	},
	save: Save,
} );
