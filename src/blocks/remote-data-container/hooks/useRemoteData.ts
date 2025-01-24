import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';

import {
	PAGINATION_CURSOR_NEXT_VARIABLE_TYPE,
	PAGINATION_CURSOR_PREVIOUS_VARIABLE_TYPE,
	PAGINATION_OFFSET_VARIABLE_TYPE,
	PAGINATION_PAGE_VARIABLE_TYPE,
	PAGINATION_PER_PAGE_VARIABLE_TYPE,
	REMOTE_DATA_REST_API_URL,
	SEARCH_INPUT_VARIABLE_TYPE,
} from '@/blocks/remote-data-container/config/constants';
import { useDebouncedState } from '@/hooks/useDebouncedState';

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

interface UsePaginationVariables {
	onFetch: ( remoteData: RemoteData ) => void;
	page: number;
	paginationQueryInput: RemoteDataQueryInput;
	perPage?: number;
	setPage: ( page: number ) => void;
	setPerPage: ( perPage: number ) => void;
	supportsCursorPagination: boolean;
	supportsOffsetPagination: boolean;
	supportsPagePagination: boolean;
	supportsPagination: boolean;
	supportsPerPage: boolean;
	totalItems?: number;
	totalPages?: number;
}

interface UsePaginationVariablesInput {
	initialPage?: number;
	initialPerPage?: number;
	inputVariables: InputVariable[];
}

export function usePaginationVariables( {
	initialPage = 1,
	initialPerPage,
	inputVariables,
}: UsePaginationVariablesInput ): UsePaginationVariables {
	const [ paginationData, setPaginationData ] = useState< RemoteDataPagination >();
	const [ page, setPage ] = useState< number >( initialPage );
	const [ perPage, setPerPage ] = useState< number | null >( initialPerPage ?? null );

	const cursorNextVariable = inputVariables?.find(
		input => input.type === PAGINATION_CURSOR_NEXT_VARIABLE_TYPE
	);
	const cursorPreviousVariable = inputVariables?.find(
		input => input.type === PAGINATION_CURSOR_PREVIOUS_VARIABLE_TYPE
	);
	const offsetVariable = inputVariables?.find(
		input => input.type === PAGINATION_OFFSET_VARIABLE_TYPE
	);
	const pageVariable = inputVariables?.find(
		input => input.type === PAGINATION_PAGE_VARIABLE_TYPE
	);
	const perPageVariable = inputVariables?.find(
		input => input.type === PAGINATION_PER_PAGE_VARIABLE_TYPE
	);

	const paginationQueryInput: RemoteDataQueryInput = {};

	// These will be amended below.
	let supportsCursorPagination = false;
	let supportsOffsetPagination = false;
	let supportsPagePagination = false;
	let setPageFn: ( page: number ) => void = () => {};

	if ( cursorNextVariable && cursorPreviousVariable ) {
		setPageFn = setPageForCursorPagination;
		supportsCursorPagination = true;
		Object.assign( paginationQueryInput, {
			[ cursorNextVariable.slug ]: paginationData?.cursorNext,
			[ cursorPreviousVariable.slug ]: paginationData?.cursorPrevious,
		} );
	} else if ( offsetVariable && perPage ) {
		setPageFn = setPage;
		supportsOffsetPagination = true;
		Object.assign( paginationQueryInput, { [ offsetVariable.slug ]: page * perPage } );
	} else if ( pageVariable ) {
		setPageFn = setPage;
		supportsPagePagination = true;
		Object.assign( paginationQueryInput, { [ pageVariable.slug ]: page } );
	}

	if ( perPageVariable && perPage ) {
		Object.assign( paginationQueryInput, { [ perPageVariable.slug ]: perPage } );
	}

	const supportsPagination =
		supportsCursorPagination || supportsPagePagination || supportsOffsetPagination;
	const totalItems = paginationData?.totalItems;
	const totalPages = totalItems && perPage ? Math.ceil( totalItems / perPage ) : undefined;

	function onFetch( remoteData: RemoteData ): void {
		if ( ! supportsPagination ) {
			return;
		}

		setPaginationData( remoteData.pagination );

		// We need a perPage value to calculate the total pages, so inpsect the results.
		if ( ! perPage && remoteData.results.length ) {
			setPerPage( remoteData.results.length );
		}
	}

	// With cursor pagination, we can only go one page at a time.
	function setPageForCursorPagination( newPage: number ): void {
		if ( newPage > page ) {
			if ( totalPages ) {
				setPage( Math.min( totalPages, page + 1 ) );
				return;
			}

			setPage( page + 1 );
			return;
		}

		if ( newPage < page ) {
			setPage( Math.max( 1, page - 1 ) );
		}
	}

	return {
		onFetch,
		page,
		paginationQueryInput,
		perPage: perPage ?? undefined,
		setPage: setPageFn,
		setPerPage: supportsPagination ? setPerPage : () => {},
		supportsCursorPagination,
		supportsOffsetPagination,
		supportsPagePagination,
		supportsPagination,
		supportsPerPage: Boolean( perPageVariable ),
		totalItems,
		totalPages,
	};
}

interface UseSearchVariables {
	searchAllowsEmptyInput: boolean;
	searchInput: string;
	searchQueryInput: RemoteDataQueryInput;
	setSearchInput: ( searchInput: string ) => void;
	supportsSearch: boolean;
}

interface UseSearchVariablesInput {
	initialSearchInput?: string;
	inputVariables: InputVariable[];
	searchInputDelayInMs?: number;
}

export function useSearchVariables( {
	initialSearchInput = '',
	inputVariables,
	searchInputDelayInMs = 500,
}: UseSearchVariablesInput ): UseSearchVariables {
	const [ searchInput, setSearchInput ] = useDebouncedState< string >(
		searchInputDelayInMs,
		initialSearchInput
	);

	const inputVariable = inputVariables?.find( input => input.type === SEARCH_INPUT_VARIABLE_TYPE );
	const supportsSearch = Boolean( inputVariable );
	const searchAllowsEmptyInput = supportsSearch && ! inputVariable?.required;
	const hasSearchInput = supportsSearch && ( searchInput || searchAllowsEmptyInput );

	return {
		searchAllowsEmptyInput,
		searchInput,
		searchQueryInput:
			hasSearchInput && inputVariable ? { [ inputVariable.slug ]: searchInput } : {},
		setSearchInput: supportsSearch ? setSearchInput : () => {},
		supportsSearch,
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

	const resolvedData = externallyManagedRemoteData ?? data;
	const resolvedUpdater = externallyManagedUpdateRemoteData ?? setData;
	const hasResolvedData = Boolean( resolvedData );

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
