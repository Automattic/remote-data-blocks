import { store } from '@wordpress/interactivity';

import { PUBLIC_STORE_NAME } from '../config/constants';

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

const initialState: ViewInitialState = {
	jobs: [],
	searchTerms: '',
};

const { state } = store< ViewStore >( PUBLIC_STORE_NAME, {
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
		updateSearchTerms: ( evt: React.ChangeEvent< HTMLInputElement > ) => {
			// TODO: Debounce
			state.searchTerms = evt.target?.value;
		},
	},
	state: {
		// It's important to enumerate all properties here instead of spreading
		// another object, so that they are captured by the object proxy wrapper.
		// Otherwise you will get an error.
		jobs: initialState.jobs,

		// We rely on the default values for blockName and restUrl, supplied by
		// wp_interactivity_state() in render.php.
	},
} );
