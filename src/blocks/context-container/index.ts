import { registerBlockType } from '@wordpress/blocks';
import { addFilter } from '@wordpress/hooks';
import { registerFormatType } from '@wordpress/rich-text';

import { formatTypeSettings } from './components/field-shortcode';
import { Edit } from './edit';
import { withBlockBinding } from './hooks/with-block-binding';
import { Save } from './save';
import { getBlocksConfig } from '../../utils/localized-block-data';
import './style.scss';

// Register a unique block definition for each of the context blocks.
Object.values( getBlocksConfig() ).forEach( blockConfig => {
	registerBlockType< ContextBlockAttributes >( blockConfig.name, {
		...blockConfig.settings,
		attributes: {
			remoteData: {
				type: 'object',
			},
		},
		edit: Edit,
		save: Save,
	} );
} );

// Register the field shortcode format type.
registerFormatType( 'remote-data-blocks/field-shortcode', formatTypeSettings );

// Filter the BlockEdit component to inject our block binding logic.
addFilter( 'editor.BlockEdit', 'remote-data-blocks/with-block-binding', withBlockBinding );
