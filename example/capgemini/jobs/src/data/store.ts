import { store } from '@wordpress/interactivity';

import { PUBLIC_STORE_NAME } from '../config/constants';

interface GenericEvent {
	key?: string;
	target: {
		value?: string;
	};
	type: string;
}

interface Job {
	id: string;
	title: string;
}

interface ViewInitialState {
	jobs: Job[];
	searchTerms: string;
}

export interface ViewState extends ViewInitialState {
	blockName: string;
	restUrl: string;
}

interface ViewStore {
	actions: {
		search: () => Promise< void >;
		updateSearchTerms: ( evt: React.ChangeEvent< HTMLInputElement > ) => void;
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
		updateSearchTerms: ( evt: GenericEvent ) => {
			// TODO: Debounce
			state.searchTerms = evt.target?.value ?? '';

			if ( evt.type === 'keydown' && evt.key === 'Enter' ) {
				void actions.search();
			}
		},
	},
	state: {
		// We rely on the initial state supplied by wp_interactivity_state() in
		// render.php, which is merged with the default initial state here.
	},
} );
