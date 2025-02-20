import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';

import { REMOTE_DATA_REST_API_URL } from '@/blocks/remote-data-container/config/constants';
import { usePaginationVariables } from '@/blocks/remote-data-container/hooks/usePaginationVariables';
import { useSearchVariables } from '@/blocks/remote-data-container/hooks/useSearchVariables';
import { validateQueryInput } from '@/utils/input-validation';
import { getBlockConfig } from '@/utils/localized-block-data';

async function fetchRemoteData( requestData: RemoteDataApiRequest ): Promise< RemoteData | null > {
	const { body } = await apiFetch< RemoteDataApiResponse >( {
		url: REMOTE_DATA_REST_API_URL,
		method: 'POST',
		data: requestData,
	} );

	if ( ! body ) {
		return null;
	}

	return {
		blockName: body.block_name,
		isCollection: body.is_collection,
		metadata: body.metadata,
		pagination: body.pagination && {
			cursorNext: body.pagination.cursor_next,
			cursorPrevious: body.pagination.cursor_previous,
			totalItems: body.pagination.total_items,
		},
		queryInput: body.query_input,
		resultId: body.result_id,
		results: body.results.map( result =>
			Object.entries( result.result ).reduce(
				( acc, [ key, value ] ) => ( {
					...acc,
					[ key ]: value.value,
				} ),
				{}
			)
		),
	};
}

interface UseRemoteData {
	data?: RemoteData;
	error?: Error;
	fetch: ( queryInput: RemoteDataQueryInput ) => Promise< void >;
	hasNextPage: boolean;
	hasPreviousPage: boolean;
	loading: boolean;
	page: number;
	perPage?: number;
	reset: () => void;
	searchInput: string;
	setPage: ( page: number ) => void;
	setPerPage: ( perPage: number ) => void;
	setSearchInput: ( searchInput: string ) => void;
	supportsCursorPagination: boolean;
	supportsOffsetPagination: boolean;
	supportsPagePagination: boolean;
	supportsPagination: boolean;
	supportsPerPage: boolean;
	supportsSearch: boolean;
	totalItems?: number;
	totalPages?: number;
}

interface UseRemoteDataInput {
	blockName: string;
	enabledOverrides?: string[];
	externallyManagedRemoteData?: RemoteData;
	externallyManagedUpdateRemoteData?: ( remoteData?: RemoteData ) => void;
	fetchOnMount?: boolean;
	initialPage?: number;
	initialPerPage?: number;
	initialSearchInput?: string;
	onSuccess?: () => void;
	queryKey: string;
}

// This hook fetches remote data and manages state for the requests.
//
// If you have another way to manage the state of the remote data, then you must
// pass in the data and a state updater function.
//
// Use case: You might be fetching data only to provide it to setAttributes,
// which is already reactive. Or you might be chaining multiple calls and
// don't need an intermediate state update / re-render.
export function useRemoteData( {
	blockName,
	enabledOverrides = [],
	externallyManagedRemoteData,
	externallyManagedUpdateRemoteData,
	fetchOnMount = false,
	initialPage,
	initialPerPage,
	initialSearchInput,
	onSuccess,
	queryKey,
}: UseRemoteDataInput ): UseRemoteData {
	const [ data, setData ] = useState< RemoteData >();
	const [ error, setError ] = useState< Error >();
	const [ loading, setLoading ] = useState< boolean >( false );

	const resolvedData = externallyManagedRemoteData ?? data;
	const resolvedUpdater = externallyManagedUpdateRemoteData ?? setData;
	const hasResolvedData = Boolean( resolvedData );

	const blockConfig = getBlockConfig( blockName );
	const query = blockConfig?.selectors?.find( selector => selector.query_key === queryKey );

	if ( ! query ) {
		// Here we intentionally throw an error instead of calling setError, because
		// this indicates a misconfiguration somewhere in our code, not a runtime /
		// query error.
		throw new Error( `Query not found for block "${ blockName }" and key "${ queryKey }".` );
	}

	const inputVariables = query.inputs;

	const {
		onFetch: onFetchForPagination,
		page,
		perPage,
		paginationQueryInput,
		supportsPagination,
		totalItems,
		totalPages,
		...paginationVariables
	} = usePaginationVariables( {
		initialPage,
		initialPerPage,
		inputVariables,
	} );
	const { searchQueryInput, searchInput, setSearchInput, supportsSearch } = useSearchVariables( {
		initialSearchInput,
		inputVariables,
	} );

	// Search and pagination are  a "managed" input variable (this hook manages its state), so if
	// there is valid search input, then we should consider the query ready to
	// execute. Note that this query might fail if the overall search input is
	// invalid; if so, the QueryInputValidationError will be returned by this hook
	// and can be inspected by the caller to determine if or how to surface it to
	// the user.
	//
	// If we add additional managed input variables (like filters), we'll need to
	// include them here.
	//
	// We also want to respond to changes in our managed pagination
	// variables, so we include them in the dependency array of the effect.
	//
	// Otherwise, if there are no managed input variables to react to, we will wait
	// for the caller of this hook to manually `fetch`.
	const shouldFetchForManagedVariables = ! error && ( hasResolvedData || fetchOnMount );

	useEffect( () => {
		if ( ! shouldFetchForManagedVariables ) {
			return;
		}

		void fetch( resolvedData?.queryInput ?? {} );
	}, [ shouldFetchForManagedVariables, page, perPage, searchInput ] );

	async function fetch( queryInput: RemoteDataQueryInput ): Promise< void > {
		const requestData: RemoteDataApiRequest = {
			block_name: blockName,
			query_key: queryKey,
			query_input: {
				...queryInput,
				...paginationQueryInput,
				...searchQueryInput,
			},
		};

		try {
			validateQueryInput( requestData.query_input, inputVariables );
		} catch ( err: unknown ) {
			resolvedUpdater( undefined );
			setError( err instanceof Error ? err : new Error( 'Query input is invalid' ) );
			return;
		}

		setLoading( true );

		const remoteData = await fetchRemoteData( requestData ).catch( () => null );

		if ( ! remoteData ) {
			resolvedUpdater( undefined );
			setLoading( false );
			return;
		}

		onFetchForPagination( remoteData );
		resolvedUpdater( { enabledOverrides, ...remoteData } );
		setLoading( false );
		onSuccess?.();
	}

	function reset(): void {
		resolvedUpdater( undefined );
		setError( undefined );
		setLoading( false );
	}

	return {
		data: resolvedData,
		error,
		fetch,
		hasNextPage: totalPages ? page < totalPages : supportsPagination,
		hasPreviousPage: page > 1,
		loading,
		page,
		perPage,
		reset,
		searchInput,
		setSearchInput,
		supportsPagination,
		supportsSearch,
		totalItems: resolvedData?.pagination?.totalItems,
		totalPages,
		...paginationVariables,
	};
}
