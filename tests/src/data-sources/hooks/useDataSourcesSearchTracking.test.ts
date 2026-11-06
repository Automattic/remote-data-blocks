import { act, renderHook } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { sendTracksEvent } from '@/blocks/remote-data-container/utils/tracks';
import {
	DATA_SOURCES_SEARCH_TRACKING_DELAY_MS,
	useDataSourcesSearchTracking,
} from '@/data-sources/hooks/useDataSourcesSearchTracking';

vi.mock( '@/blocks/remote-data-container/utils/tracks', () => ( {
	sendTracksEvent: vi.fn(),
} ) );

describe( 'useDataSourcesSearchTracking', () => {
	beforeEach( () => {
		vi.useFakeTimers();
		vi.clearAllMocks();
	} );

	afterEach( () => {
		vi.useRealTimers();
	} );

	function type(
		track: ( search: string | undefined ) => void,
		values: Array< string | undefined >
	): void {
		act( () => {
			values.forEach( value => track( value ) );
		} );
	}

	it( 'records one event with the final query length after the debounce delay', () => {
		const { result } = renderHook( () => useDataSourcesSearchTracking() );

		type( result.current, [ 's', 'sh', 'sho', 'shop' ] );
		expect( sendTracksEvent ).not.toHaveBeenCalled();

		act( () => vi.advanceTimersByTime( DATA_SOURCES_SEARCH_TRACKING_DELAY_MS ) );

		expect( sendTracksEvent ).toHaveBeenCalledTimes( 1 );
		expect( sendTracksEvent ).toHaveBeenCalledWith( 'data_sources_searched', { query_length: 4 } );
	} );

	it( 'does not send the query text', () => {
		const { result } = renderHook( () => useDataSourcesSearchTracking() );

		type( result.current, [ 'secret' ] );
		act( () => vi.advanceTimersByTime( DATA_SOURCES_SEARCH_TRACKING_DELAY_MS ) );

		expect( JSON.stringify( vi.mocked( sendTracksEvent ).mock.calls ) ).not.toContain( 'secret' );
	} );

	it( 'does not record clearing the search', () => {
		const { result } = renderHook( () => useDataSourcesSearchTracking() );

		type( result.current, [ 'shop' ] );
		act( () => vi.advanceTimersByTime( DATA_SOURCES_SEARCH_TRACKING_DELAY_MS ) );
		type( result.current, [ '  ' ] );
		act( () => vi.advanceTimersByTime( DATA_SOURCES_SEARCH_TRACKING_DELAY_MS ) );
		type( result.current, [ 'shop' ] );
		act( () => vi.advanceTimersByTime( DATA_SOURCES_SEARCH_TRACKING_DELAY_MS ) );
		type( result.current, [ undefined ] );
		act( () => vi.advanceTimersByTime( DATA_SOURCES_SEARCH_TRACKING_DELAY_MS ) );

		expect( sendTracksEvent ).toHaveBeenCalledTimes( 2 );
		expect( sendTracksEvent ).not.toHaveBeenCalledWith( 'data_sources_searched', {
			query_length: 0,
		} );
	} );

	it( 'does not record the same query twice in a row, but does after it was cleared', () => {
		const { result } = renderHook( () => useDataSourcesSearchTracking() );

		type( result.current, [ 'shop' ] );
		act( () => vi.advanceTimersByTime( DATA_SOURCES_SEARCH_TRACKING_DELAY_MS ) );
		type( result.current, [ 'shop ' ] );
		act( () => vi.advanceTimersByTime( DATA_SOURCES_SEARCH_TRACKING_DELAY_MS ) );
		expect( sendTracksEvent ).toHaveBeenCalledTimes( 1 );

		type( result.current, [ '' ] );
		act( () => vi.advanceTimersByTime( DATA_SOURCES_SEARCH_TRACKING_DELAY_MS ) );
		type( result.current, [ 'shop' ] );
		act( () => vi.advanceTimersByTime( DATA_SOURCES_SEARCH_TRACKING_DELAY_MS ) );
		expect( sendTracksEvent ).toHaveBeenCalledTimes( 2 );
	} );

	it( 'does not record a pending search after unmount', () => {
		const { result, unmount } = renderHook( () => useDataSourcesSearchTracking() );

		type( result.current, [ 'shop' ] );
		unmount();
		act( () => vi.advanceTimersByTime( DATA_SOURCES_SEARCH_TRACKING_DELAY_MS ) );

		expect( sendTracksEvent ).not.toHaveBeenCalled();
	} );
} );
