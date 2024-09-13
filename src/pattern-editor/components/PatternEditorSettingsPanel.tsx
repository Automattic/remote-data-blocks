import { SelectControl } from '@wordpress/components';
import { PluginDocumentSettingPanel } from '@wordpress/editor';

import { PATTERN_BLOCK_TYPE_POST_META_KEY } from '@/config/constants';
import { useEditedPostAttribute } from '@/hooks/useEditedPostAttribute';
import { usePostMeta } from '@/hooks/usePostMeta';
import { __ } from '@/utils/i18n';
import { getBlocksConfig } from '@/utils/localized-block-data';

export function PatternEditorSettingsPanel() {
	const { postId, postType } = useEditedPostAttribute( getEditedPostAttribute => ( {
		postId: getEditedPostAttribute< number >( 'id' ) ?? 0,
		postType: getEditedPostAttribute< string >( 'type' ) ?? '',
	} ) );
	const { postMeta, updatePostMeta } = usePostMeta( postId, postType );

	if ( ! postId || postType !== 'wp_block' ) {
		return null;
	}

	const blocksConfig = getBlocksConfig();
	const blockType = String( postMeta?.[ PATTERN_BLOCK_TYPE_POST_META_KEY ] ?? '' );

	function updateBlockTypes( newBlockType: string ) {
		updatePostMeta( { ...postMeta, [ PATTERN_BLOCK_TYPE_POST_META_KEY ]: newBlockType } );
	}

	const options = Object.entries( blocksConfig ).map( ( [ value, blockConfig ] ) => {
		return { label: blockConfig.settings.title, value };
	} );

	return (
		<PluginDocumentSettingPanel
			name="pattern-editor-settings-panel"
			title={ __( 'Remote Data Blocks' ) }
		>
			<>
				<p>{ __( 'Choose a Remote Data Block type that is associated with this pattern.' ) }</p>
				<SelectControl
					label={ __( 'Block type' ) }
					name="block-types"
					options={ [ { label: __( 'Select a block' ), value: '' }, ...options ] }
					onChange={ updateBlockTypes }
					value={ blockType }
				/>
			</>
		</PluginDocumentSettingPanel>
	);
}
