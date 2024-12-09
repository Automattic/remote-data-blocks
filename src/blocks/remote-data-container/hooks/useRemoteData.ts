import apiFetch from '@wordpress/api-fetch';
import { useState } from '@wordpress/element';

import { REMOTE_DATA_REST_API_URL } from '@/blocks/remote-data-container/config/constants';

export async function fetchRemoteData(
	requestData: RemoteDataApiRequest
): Promise< RemoteData | null > {
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

// This hook fetches remote data and provides state for the data and loading
// status. If you do not need a separate state update for the data, you can
// instruct the `execute` function to skip it.
//
// Use case: You might be fetching data only to provide it to setAttributes,
// which is already reactive. Or you might be chaining multiple calls and
// don't need an intermediate state update / re-render.
export function useRemoteData( blockName: string, queryKey: string ) {
	const [ data, setData ] = useState< RemoteData | null >( null );
	const [ loading, setLoading ] = useState< boolean >( false );

	async function execute(
		queryInput: RemoteDataQueryInput,
		updateDataState = true
	): Promise< RemoteData | null > {
		setLoading( true );

		const requestData: RemoteDataApiRequest = {
			block_name: blockName,
			query_key: queryKey,
			query_input: queryInput,
		};

		const remoteData = await fetchRemoteData( requestData ).catch( () => null );

		if ( updateDataState ) {
			setData( remoteData );
		}

		setLoading( false );

		return remoteData;
	}

	return { data, execute, loading };
}
