import { useCallback, useEffect, useRef } from '@wordpress/element';

import { sendTracksEvent } from '@/blocks/remote-data-container/utils/tracks';

export const DATA_SOURCES_SEARCH_TRACKING_DELAY_MS = 1000;

/**
 * Returns a callback that records a debounced Tracks event when the data
 * sources list search changes. Only the query length is sent, never the query.
 */
export function useDataSourcesSearchTracking(): ( search: string | undefined ) => void {
	const timer = useRef< ReturnType< typeof setTimeout > | null >( null );
	const lastTrackedSearch = useRef< string >( '' );

	useEffect(
		() => () => {
			if ( timer.current ) {
				clearTimeout( timer.current );
			}
		},
		[]
	);

	return useCallback( ( search: string | undefined ) => {
		if ( timer.current ) {
			clearTimeout( timer.current );
		}

		const query = ( search ?? '' ).trim();

		timer.current = setTimeout( () => {
			// Don't record clearing the search box, or the same query twice in a row.
			if ( query === '' || query === lastTrackedSearch.current ) {
				lastTrackedSearch.current = query;
				return;
			}

			lastTrackedSearch.current = query;
			sendTracksEvent( 'data_sources_searched', { query_length: query.length } );
		}, DATA_SOURCES_SEARCH_TRACKING_DELAY_MS );
	}, [] );
}
