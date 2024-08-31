import { registerPlugin } from '@wordpress/plugins';

import PatternEditorSettingsPanel from '@/pattern-editor/PatternEditorSettingsPanel';

registerPlugin( 'remote-data-blocks-settings', {
	render: PatternEditorSettingsPanel,
	icon: 'admin-settings',
} );
