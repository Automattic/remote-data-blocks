import { useState } from '@wordpress/element';

import { SEARCH_INPUT_VARIABLE_TYPE } from '@/blocks/remote-data-container/config/constants';
import { useDebouncedState } from '@/hooks/useDebouncedState';

interface UseSearchVariables {
	hasSearchInput: boolean;
	searchInput: string;
	searchQueryInput: RemoteDataQueryInput;
	settledSearchInput: string;
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
	const [ searchInput, setDraftSearchInput ] = useState( initialSearchInput );
	const [ settledSearchInput, setSettledSearchInput ] = useDebouncedState< string >(
		searchInputDelayInMs,
		initialSearchInput
	);

	const inputVariable = inputVariables?.find( input => input.type === SEARCH_INPUT_VARIABLE_TYPE );
	const supportsSearch = Boolean( inputVariable );
	const searchAllowsEmptyInput = supportsSearch && ! inputVariable?.required;
	const hasSearchInput = supportsSearch && Boolean( settledSearchInput || searchAllowsEmptyInput );

	function setSearchInput( value: string ): void {
		setDraftSearchInput( value );
		// Clearing a required search cancels the request without waiting for the debounce.
		setSettledSearchInput( value, ! value && ! searchAllowsEmptyInput );
	}

	return {
		hasSearchInput,
		searchInput,
		searchQueryInput: supportsSearch
			? { [ inputVariable?.slug ?? '' ]: hasSearchInput ? settledSearchInput : null }
			: {},
		settledSearchInput,
		setSearchInput,
		supportsSearch,
	};
}
