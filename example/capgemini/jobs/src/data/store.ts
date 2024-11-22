import { getContext, store } from '@wordpress/interactivity';

import { PUBLIC_STORE_NAME } from '../config/constants';

interface GenericEvent {
	key?: string;
	target: {
		value?: string;
	};
	type: string;
}

interface RawFilter {
	count: number;
	value: string;
}

interface Filter {
	id: string;
	count: number;
	title: string;
	type: string;
}

interface FilterGroup {
	filters: Filter[];
	title: string;
	type: string;
}

interface Job {
	id: string;
	title: string;
}

interface ViewState {
	activeFilters: Filter[];
	blockName: string;
	countryCode: string;
	filterGroups: FilterGroup[];
	jobs: Job[];
	page: number;
	restUrl: string;
	searchTerms: string;
	size: number;
}

interface ViewStore {
	actions: {
		search: () => Promise< void >;
		toggleFilter: () => void;
		updateSearchTerms: ( evt: GenericEvent ) => void;
	};
	state: ViewState;
}

async function apiRequest(
	state: ViewState,
	queryKey: string,
	queryInput: Record< string, string | number >
): Promise< RemoteDataApiResult[] > {
	const response = await window.fetch( state.restUrl, {
		body: JSON.stringify( {
			block_name: state.blockName,
			query_key: queryKey,
			query_input: queryInput,
		} ),
		headers: {
			'Content-Type': 'application/json',
		},
		method: 'POST',
	} );

	const { body } = ( await response.json() ) as RemoteDataApiResponse;

	return body?.results ?? [];
}

const { actions, state } = store< ViewStore >( PUBLIC_STORE_NAME, {
	actions: {
		search: async (): Promise< void > => {
			const filters = state.activeFilters.reduce< Record< string, string[] > >(
				( acc: Record< string, string[] >, filter ) => ( {
					...acc,
					[ filter.type ]: [ ...( acc[ filter.type ] ?? [] ), filter.title ],
				} ),
				{}
			);
			const filterQueryVars = Object.fromEntries(
				Object.entries( filters ).map( ( [ key, value ] ) => [ key, value.join( ',' ) ] )
			);

			const queryInput = {
				...filterQueryVars,
				country_code: state.countryCode,
				page: state.page,
				search: state.searchTerms,
				size: state.size,
			};

			const jobResults = await apiRequest( state, '__DISPLAY__', queryInput );
			state.jobs =
				jobResults.map( result => ( {
					id: result.result?.id?.value ?? 'id',
					title: result.result?.title?.value ?? 'title',
				} ) ) ?? [];

			const filterResults = await apiRequest(
				state,
				'RemoteDataBlocks\\Example\\Capgemini\\Jobs\\CapgeminiJobFiltersQuery',
				{ country_code: state.countryCode, search: state.searchTerms }
			);
			console.log( filterResults );
			state.filterGroups = filterResults.map( ( { result } ) => ( {
				filters: ( JSON.parse( result?.items?.value ?? '[]' ) as RawFilter[] ).map( filter => ( {
					id: `${ result?.type?.value }_${ filter.value }`,
					count: filter.count ?? 0,
					title: filter.value,
					type: result?.type?.value ?? '',
				} ) ),
				title: result?.title?.value ?? result?.type?.value ?? '',
				type: result?.type?.value ?? '',
			} ) );
			state.activeFilters = [];
		},
		toggleFilter: () => {
			const { filter } = getContext< { filter: Filter } >();
			const findFilter = ( activeFilter: Filter ) =>
				filter.type === activeFilter.type && filter.title === activeFilter.title;
			const isActive = state.activeFilters.some( findFilter );

			if ( isActive ) {
				state.activeFilters = state.activeFilters.filter(
					activeFilter => ! findFilter( activeFilter )
				);
			} else {
				state.activeFilters.push( filter );
			}

			void actions.search();
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
