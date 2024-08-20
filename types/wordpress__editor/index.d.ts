/**
 * The types provided by @types/wordpress__editor are incomplete.
 */

declare module '@wordpress/editor' {
	type EditorStoreSelectors = {
		// Not all properties of Post | Page are included, so widen to string and
		// allow caller to provide the return type.
		getEditedPostAttribute: < T >( attributeName: string ) => T | undefined;
	};

	function PluginDocumentSettingPanel( props: {
		children: JSX.Element;
		name: string;
		title: string;
	} ): JSX.Element;
}
