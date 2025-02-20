import { useDebounce } from '@wordpress/compose';
import { useCallback, useEffect } from '@wordpress/element';

import { getSalesforceD2CAuthToken } from '@/data-sources/api-clients/auth';
import { useQuery } from '@/hooks/useQuery';

export const useSalesforceD2CAuth = ( domain: string, clientId: string, clientSecret: string ) => {
	const queryFn = useCallback( async () => {
		return getSalesforceD2CAuthToken( domain, clientId, clientSecret );
	}, [ domain, clientId, clientSecret ] );

	const {
		data: token,
		isLoading: fetchingToken,
		error: tokenError,
		refetch: fetchToken,
	} = useQuery( queryFn, { manualFetchOnly: true } );

	const debouncedFetchToken = useDebounce( fetchToken, 500 );
	// eslint-disable-next-line react-hooks/exhaustive-deps
	useEffect( debouncedFetchToken, [ domain, clientId, clientSecret ] );

	return { token, fetchingToken, fetchToken, tokenError };
};
