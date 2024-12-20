import { useEffect, useRef, useState } from '@wordpress/element';

import { useRemoteData } from '@/blocks/remote-data-container/hooks/useRemoteData';

export interface UseSearchResultsInput {
	allowEmptySearchTerms?: boolean;
	blockName: string;
	debounceInMs?: number;
	queryKey: string;
}
export function useSearchResults( {
	allowEmptySearchTerms = true,
	blockName,
	debounceInMs = 200,
	queryKey,
}: UseSearchResultsInput ) {
	const [ searchTerms, setSearchTerms ] = useState< string >( '' );
	const { data, execute, loading } = useRemoteData( blockName, queryKey );
	const timer = useRef< NodeJS.Timeout >();

	function onSubmit(): void {
		void execute( { search_terms: searchTerms } );
	}

	function onKeyDown( event: React.KeyboardEvent< HTMLInputElement > ): void {
		if ( event.code !== 'Enter' ) {
			return;
		}

		event.preventDefault();
		onSubmit();
	}

	useEffect( () => {
		if ( allowEmptySearchTerms || searchTerms ) {
			// Debounce the search term input.
			const newTimer = setTimeout( onSubmit, debounceInMs );
			clearTimeout( timer.current );
			timer.current = newTimer;
		}

		return () => clearTimeout( timer.current );
	}, [ allowEmptySearchTerms, searchTerms ] );

	return {
		loading,
		onKeyDown,
		results: data?.results,
		searchTerms,
		setSearchTerms,
	};
}
