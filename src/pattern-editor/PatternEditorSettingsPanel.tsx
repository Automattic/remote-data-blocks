import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import {
	EditorStoreSelectors,
	PluginDocumentSettingPanel,
	store as editorStore,
} from '@wordpress/editor';
import { __ } from '@wordpress/i18n';

interface SelectReturnValue {
	categoryIds: number[];
	postContent: string;
	postId?: string;
	postType: string;
	syncStatus: string;
}

const PatternEditorSettingsPanel = () => {
	const { categoryIds, postContent, postId, postType, syncStatus } = useSelect<
		EditorStoreSelectors,
		SelectReturnValue
	>( select => {
		const { getEditedPostAttribute } = select( editorStore );

		/**
		 * We have access to the entirety of WordPress Data in the context of the Pattern Editor sidebar here.
		 * Inspect:
		 * `wp.data.select('core/editor').getCurrentPost()`
		 * To see what's available as a Post Attribute
		 */

		return {
			categoryIds: getEditedPostAttribute< number[] >( 'wp_pattern_category' ) ?? [],
			postContent: getEditedPostAttribute< string >( 'content' ) ?? '',
			postId: String( getEditedPostAttribute< number >( 'id' ) ),
			postType: getEditedPostAttribute< string >( 'type' ) ?? '',
			syncStatus: getEditedPostAttribute< string >( 'wp_pattern_sync_status' ) ?? '',
		};
	}, [] );

	const [
		// This is the post meta for the "saved" version of this post. It's not currently updated in real-time.
		postMeta,

		// We can use this function to update the post meta if needed.
		_updatePostMeta,
	] = useEntityProp( 'postType', 'wp_block', 'meta', postId ) as [
		Record< string, unknown >,
		( meta: Record< string, unknown > ) => void,
		unknown
	];

	if ( postType !== 'wp_block' ) {
		return null;
	}

	const isSynced = syncStatus !== 'unsynced';

	console.log( {
		categoryIds,
		isSynced,
		postContent,
		postId,
		postMeta,
		postType,
		syncStatus,
	} );

	return (
		<PluginDocumentSettingPanel
			name="pattern-editor-settings-panel"
			title={ __( 'Remote Data Patterns' ) }
		>
			<p>Settings for the pattern editor will go here.</p>
		</PluginDocumentSettingPanel>
	);
};

export default PatternEditorSettingsPanel;
