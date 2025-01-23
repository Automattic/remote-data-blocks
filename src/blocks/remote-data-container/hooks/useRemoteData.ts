import apiFetch from '@wordpress/api-fetch';
import { useState } from '@wordpress/element';

import { REMOTE_DATA_REST_API_URL } from '@/blocks/remote-data-container/config/constants';

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
		pagination: {
			nextInputVariables: body.pagination.next_input_variables ?? undefined,
			previousInputVariables: body.pagination.previous_input_variables ?? undefined,
			totalItems: body.pagination.total_items ?? undefined,
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
	fetchNextPage: () => Promise< void >;
	fetchPreviousPage: () => Promise< void >;
	hasNextPage: boolean;
	hasPreviousPage: boolean;
	loading: boolean;
	reset: () => void;
	totalItems?: number;
}

interface UseRemoteDataInput {
	blockName: string;
	enabledOverrides?: string[];
	externallyManagedRemoteData?: RemoteData;
	externallyManagedUpdateRemoteData?: ( remoteData?: RemoteData ) => void;
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
	onSuccess,
	queryKey,
}: UseRemoteDataInput ): UseRemoteData {
	const [ data, setData ] = useState< RemoteData >();
	const [ loading, setLoading ] = useState< boolean >( false );

	const resolvedData = externallyManagedRemoteData ?? data;
	const resolvedUpdater = externallyManagedUpdateRemoteData ?? setData;

	async function fetch( queryInput: RemoteDataQueryInput ): Promise< void > {
		setLoading( true );

		const requestData: RemoteDataApiRequest = {
			block_name: blockName,
			query_key: queryKey,
			query_input: queryInput,
		};

		const remoteData = await fetchRemoteData( requestData ).catch( () => null );

		if ( ! remoteData ) {
			resolvedUpdater( undefined );
			setLoading( false );
			return;
		}

		resolvedUpdater( { enabledOverrides, ...remoteData } );
		setLoading( false );
		onSuccess?.();
	}

	async function refetch( queryInputOverrides?: RemoteDataQueryInput ): Promise< void > {
		return fetch( { ...resolvedData?.queryInput, ...queryInputOverrides } );
	}

	async function fetchNextPage(): Promise< void > {
		if ( ! resolvedData?.pagination?.nextInputVariables ) {
			return;
		}

		return refetch( resolvedData.pagination.nextInputVariables );
	}

	async function fetchPreviousPage(): Promise< void > {
		if ( ! resolvedData?.pagination?.previousInputVariables ) {
			return;
		}

		return refetch( resolvedData.pagination.previousInputVariables );
	}

	function reset(): void {
		resolvedUpdater( undefined );
	}

	return {
		data: resolvedData,
		fetch,
		fetchNextPage,
		fetchPreviousPage,
		hasNextPage: Boolean( data?.pagination?.nextInputVariables ),
		hasPreviousPage: Boolean( data?.pagination?.previousInputVariables ),
		loading,
		reset,
		totalItems: resolvedData?.pagination?.totalItems,
	};
}
