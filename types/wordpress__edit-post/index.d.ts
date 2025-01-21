import '@wordpress/block-editor';

/**
 * The types provided by @types/wordpress__edit-post are out of date.
 */

declare module '@wordpress/edit-post' {
	interface EditPostStoreActions {
		openGeneralSidebar: ( name: string? ) => Promise< void >;
	}

	interface EditPostStoreSelectors {
		isEditorSidebarOpened: () => boolean;
	}
}
