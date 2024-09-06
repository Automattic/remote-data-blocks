import { useDebounce } from '@wordpress/compose';
import { useEffect, useMemo, useCallback } from '@wordpress/element';

import { GoogleApi } from '@/data-sources/api/google';
import { useQuery } from '@/hooks/useQuery';

export const useGoogleSpreadsheetsOptions = ( token: string | null ) => {
	const api = useMemo( () => new GoogleApi( token ), [ token ] );

	const queryFn = useCallback( async () => {
		if ( ! token ) {
			return null;
		}
		return api.getSpreadsheetsOptions();
	}, [ api, token ] );

	const {
		data: spreadsheets,
		isLoading: isLoadingSpreadsheets,
		error: errorSpreadsheets,
		refetch: refetchSpreadsheets,
	} = useQuery( queryFn, { manualFetchOnly: true } );

	const debouncedFetchSpreadsheets = useDebounce( refetchSpreadsheets, 500 );
	useEffect( debouncedFetchSpreadsheets, [ token, debouncedFetchSpreadsheets ] );

	return { spreadsheets, isLoadingSpreadsheets, errorSpreadsheets, refetchSpreadsheets };
};

export const useGoogleSheetsOptions = ( token: string | null, spreadsheetId: string ) => {
	const api = useMemo( () => new GoogleApi( token ), [ token ] );

	const queryFn = useCallback( async () => {
		if ( ! token || ! spreadsheetId ) {
			return null;
		}
		return api.getSheetsOptions( spreadsheetId );
	}, [ api, token, spreadsheetId ] );

	const {
		data: sheets,
		isLoading: isLoadingSheets,
		error: errorSheets,
		refetch: refetchSheets,
	} = useQuery( queryFn, { manualFetchOnly: true } );

	const debouncedFetchSheets = useDebounce( refetchSheets, 500 );
	useEffect( debouncedFetchSheets, [ token, debouncedFetchSheets ] );

	return { sheets, isLoadingSheets, errorSheets, refetchSheets };
};
