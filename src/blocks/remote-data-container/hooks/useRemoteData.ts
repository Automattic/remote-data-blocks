import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';

import { REMOTE_DATA_REST_API_URL } from '@/blocks/remote-data-container/config/constants';
import { usePaginationVariables } from '@/blocks/remote-data-container/hooks/usePaginationVariables';
import { useSearchVariables } from '@/blocks/remote-data-container/hooks/useSearchVariables';

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
	fetch: ( queryInput: RemoteDataQueryInput ) => Promise< void >;
	hasNextPage: boolean;
	hasPreviousPage: boolean;
	loading: boolean;
	page: number;
	perPage?: number;
	reset: () => void;
	searchAllowsEmptyInput: boolean;
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
	initialPage?: number;
	initialPerPage?: number;
	initialSearchInput?: string;
	inputVariables?: InputVariable[];
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
	initialPage,
	initialPerPage,
	initialSearchInput,
	inputVariables = [],
	onSuccess,
	queryKey,
}: UseRemoteDataInput ): UseRemoteData {
	const [ data, setData ] = useState< RemoteData >();
	const [ loading, setLoading ] = useState< boolean >( false );

	const resolvedData = externallyManagedRemoteData ?? data;
	const resolvedUpdater = externallyManagedUpdateRemoteData ?? setData;
	const hasResolvedData = Boolean( resolvedData );

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
	const { searchQueryInput, searchAllowsEmptyInput, searchInput, setSearchInput, supportsSearch } =
		useSearchVariables( {
			initialSearchInput,
			inputVariables,
		} );

	useEffect( () => {
		if ( ! hasResolvedData ) {
			return;
		}

		void fetch( resolvedData?.queryInput ?? {} );
	}, [ hasResolvedData, page, perPage, searchInput ] );

	async function fetch( queryInput: RemoteDataQueryInput ): Promise< void > {
		setLoading( true );

		const requestData: RemoteDataApiRequest = {
			block_name: blockName,
			query_key: queryKey,
			query_input: {
				...queryInput,
				...paginationQueryInput,
				...searchQueryInput,
			},
		};

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
	}

	return {
		data: resolvedData,
		fetch,
		hasNextPage: totalPages ? page < totalPages : supportsPagination,
		hasPreviousPage: page > 1,
		loading,
		page,
		perPage,
		reset,
		searchAllowsEmptyInput,
		searchInput,
		setSearchInput,
		supportsPagination,
		supportsSearch,
		totalItems: resolvedData?.pagination?.totalItems,
		totalPages,
		...paginationVariables,
	};
}
