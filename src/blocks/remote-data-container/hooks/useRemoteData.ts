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
	loading: boolean;
	reset: () => void;
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

	function reset(): void {
		resolvedUpdater( undefined );
	}

	return {
		data: resolvedData,
		fetch,
		loading,
		reset,
	};
}
