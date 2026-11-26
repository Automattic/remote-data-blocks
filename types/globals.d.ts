import type { DataViews, filterSortAndPaginate } from '@wordpress/dataviews/wp';

declare global {
	var REMOTE_DATA_BLOCKS: LocalizedBlockData | undefined;
	var REMOTE_DATA_BLOCKS_SETTINGS: LocalizedSettingsData | undefined;
	var REMOTE_DATA_BLOCKS_EDITOR_HTML:
		| {
				attributes: Record< string, string[] >;
				protocols: string[];
				uriAttributes: string[];
				styleProperties: string[];
		  }
		| undefined;
	var LockedPrivateDataViews: {
		filterSortAndPaginate: typeof filterSortAndPaginate;
		DataViews: typeof DataViews;
	};
}

export {};
