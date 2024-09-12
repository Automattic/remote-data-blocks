import { registerBlockType } from '@wordpress/blocks';
import { addFilter } from '@wordpress/hooks';
import { registerFormatType } from '@wordpress/rich-text';

import { formatTypeSettings } from '@/blocks/remote-data-container/components/field-shortcode/field-shortcode';
import { Edit } from '@/blocks/remote-data-container/edit';
import { withBlockBinding } from '@/blocks/remote-data-container/filters/with-block-binding';
import { Save } from '@/blocks/remote-data-container/save';
import { getBlocksConfig } from '@/utils/localized-block-data';
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
		edit: Edit,
		save: Save,
	} );
} );

// Register the field shortcode format type.
registerFormatType( 'remote-data-blocks/field-shortcode', formatTypeSettings );

// Filter the BlockEdit component to inject our block binding logic.
addFilter( 'editor.BlockEdit', 'remote-data-blocks/with-block-binding', withBlockBinding );
