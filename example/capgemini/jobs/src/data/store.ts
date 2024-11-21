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
	blockName?: string;
	restUrl?: string;
}

interface ViewStore {
	actions: {
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
