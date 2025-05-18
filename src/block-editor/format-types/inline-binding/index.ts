import { registerFormatType } from '@wordpress/rich-text';

import { InlineBindingButton } from '@/block-editor/format-types/inline-binding/components/InlineBindingButton';
import { formatTypeSettings } from '@/block-editor/format-types/inline-binding/settings';

// Register the inline binding format type.
registerFormatType( 'remote-data-blocks/inline-binding', {
	...formatTypeSettings,
	edit: InlineBindingButton,
} );
