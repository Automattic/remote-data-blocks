import { getContext, store } from '@wordpress/interactivity';

import { PUBLIC_STORE_NAME } from '../config/constants';

interface Job {
	id: string;
	title: string;
}

interface Filter {
	type: string;
	items: { value: string; count: number }[];
}

interface ViewInitialState {
	jobs: Job[];
	filters: Filter[];
	selectedFilters: { filter: string; value: string }[];
	isFilterSelected: () => boolean;
	searchTerms: string;
}

export interface ViewState extends ViewInitialState {
	blockName: string;
	restUrl: string;
}

interface ViewStore {
	actions: {
		search: () => Promise< void >;
		clearFilter: ( filter: string ) => void;
		clearAllFilters: () => void;
		updateSearchTerms: ( evt: React.ChangeEvent< HTMLInputElement > ) => void;
		toggleFilter: () => void;
	};
	state: ViewState;
}

const { actions, state } = store< ViewStore >( PUBLIC_STORE_NAME, {
	actions: {
		search: async (): Promise< void > => {
			const response = await window.fetch( state.restUrl, {
				body: JSON.stringify( {
					block_name: state.blockName,
					query_key: '__DISPLAY__',
					query_input: {
						search: state.searchTerms,
						...state.selectedFilters.reduce( ( acc, filter ) => {
							acc[ filter.filter ] = filter.value;
							return acc;
						}, {} as Record< string, string > ),
					},
				} ),
				headers: {
					'Content-Type': 'application/json',
				},
				method: 'POST',
			} );
			const { body } = ( await response.json() ) as RemoteDataApiResponse;

			state.jobs =
				body?.results.map( result => ( {
					id: result.result?.id?.value ?? 'id',
					title: result.result?.title?.value ?? 'title',
				} ) ) ?? [];
		},
		updateSearchTerms: event => {
			// TODO: Debounce
			state.searchTerms = event.target.value;

			// if ( evt.type === 'keydown' && 'key' in evt && evt.key === 'Enter' ) {
			// 	void actions.search();
			// }
		},
		clearFilter: ( filter: string ) => {
			state.selectedFilters = state.selectedFilters.filter( _filter => _filter.filter !== filter );
		},
		clearAllFilters: () => {
			state.selectedFilters = [];
		},
		toggleFilter: ( event: { target: HTMLInputElement } ) => {
			const context: { item: { value: string } } = getContext();

			if ( event.target.checked ) {
				state.selectedFilters.push( {
					filter: event.target.name,
					value: context.item.value,
				} );
			} else {
				state.selectedFilters = state.selectedFilters.filter(
					_filter => _filter.filter !== event.target.name || _filter.value !== context.item.value
				);
			}
		},
	} as ViewStore[ 'actions' ],
	state: {
		selectedFilters: [],
		// We rely on the initial state supplied by wp_interactivity_state() in
		// render.php, which is merged with the default initial state here.
	},
} );
