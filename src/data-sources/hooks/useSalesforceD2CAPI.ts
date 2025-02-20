import { useDebounce } from '@wordpress/compose';
import { useCallback, useEffect, useMemo } from '@wordpress/element';

import { SalesforceD2CApi } from '@/data-sources/api-clients/salesforceD2C';
import { useQuery } from '@/hooks/useQuery';

export const useSalesforceD2CWebstoresOptions = ( token: string | null, domain: string | null ) => {
	const api = useMemo( () => new SalesforceD2CApi( token, domain ), [ token, domain ] );

	const queryFn = useCallback( async () => {
		if ( ! token || ! domain ) {
			return null;
		}
		return api.getWebStoresOptions();
	}, [ api, token, domain ] );

	const {
		data: webstores,
		isLoading: isLoadingWebstores,
		error: errorWebstores,
		refetch: refetchWebstores,
	} = useQuery( queryFn, { manualFetchOnly: true } );

	const debouncedRefetchWebstores = useDebounce( refetchWebstores, 500 );
	useEffect( debouncedRefetchWebstores, [ token, domain, debouncedRefetchWebstores ] );

	return { webstores, isLoadingWebstores, errorWebstores, refetchWebstores };
};
