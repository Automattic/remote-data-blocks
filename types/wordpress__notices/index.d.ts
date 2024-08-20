import '@wordpress/notices';

declare module '@wordpress/notices' {
	interface WPNoticeAction {
		label: string;
		url?: string;
		onClick?: () => void;
	}

	interface CreateNoticeOptions {
		context?: string;
		id?: string;
		isDismissible?: boolean;
		type?: 'default' | 'snackbar';
		speak?: boolean;
		actions?: WPNoticeAction[];
		icon?: string | null;
		explicitDismiss?: boolean;
		onDismiss?: () => void;
		__unstableHTML?: boolean;
	}

	export interface WPNotice {
		id: string;
		status: string;
		content: string;
		spokenMessage: string | null;
		__unstableHTML?: boolean;
		isDismissible: boolean;
		actions: WPNoticeAction[];
		type: 'default' | 'snackbar';
		icon: string | null;
		explicitDismiss: boolean;
		onDismiss?: () => void;
	}

	interface CreateNoticeReturn {
		type: 'CREATE_NOTICE';
		context: string;
		notice: WPNotice;
	}

	interface RemoveNoticeReturn {
		type: 'REMOVE_NOTICE';
		context: string;
		id: string;
	}

	export interface NoticeStoreActions {
		createSuccessNotice: ( content: string, options?: CreateNoticeOptions ) => CreateNoticeReturn;
		createErrorNotice: ( content: string, options?: CreateNoticeOptions ) => CreateNoticeReturn;
		removeNotice: ( id: string, context?: string ) => RemoveNoticeReturn;
	}

	export interface NoticeStoreSelectors {
		getNotices: ( state: object = {}, context?: string ) => WPNotice[];
	}
}
