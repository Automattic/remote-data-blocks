import type { StoreDescriptor } from '@wordpress/data';

/**
 * The types provided by @types/wordpress__edit-post are out of date.
 */

interface EditPostStoreDescriptor extends StoreDescriptor {
	name: 'core/edit-post';
}

declare module '@wordpress/edit-post' {
	const store: EditPostStoreDescriptor;

	interface EditPostStoreActions {
		openGeneralSidebar: ( name: string? ) => Promise< void >;
	}

	interface EditPostStoreSelectors {
		isEditorSidebarOpened: () => boolean;
	}
}
