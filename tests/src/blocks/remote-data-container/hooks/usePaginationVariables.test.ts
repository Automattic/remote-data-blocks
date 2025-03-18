import { act, renderHook } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import {
	PAGINATION_CURSOR_NEXT_VARIABLE_TYPE,
	PAGINATION_CURSOR_PREVIOUS_VARIABLE_TYPE,
	PAGINATION_CURSOR_VARIABLE_TYPE,
	PAGINATION_OFFSET_VARIABLE_TYPE,
	PAGINATION_PAGE_VARIABLE_TYPE,
	PAGINATION_PER_PAGE_VARIABLE_TYPE,
} from '@/blocks/remote-data-container/config/constants';
import { usePaginationVariables } from '@/blocks/remote-data-container/hooks/usePaginationVariables';

function generateRemoteData(
	resultCount: number,
	paginationData: RemoteDataPagination
): RemoteData {
	return {
		blockName: 'test/block',
		queryKey: 'test-query',
		queryInputs: [],
		metadata: {},
		resultId: 'result-id',
		results: Array.from( { length: resultCount }, () => ( {
			result: {},
			uuid: 'uuid',
		} ) ),
		pagination: paginationData,
	};
}

describe( 'usePaginationVariables', () => {
	it( 'provides static variables when pagination is not supported', () => {
		const inputVariables: InputVariable[] = [];

		const { result } = renderHook( () => usePaginationVariables( { inputVariables } ) );

		expect( result.current.page ).toBe( 1 );
		expect( result.current.perPage ).toBeUndefined();
		expect( result.current.supportsPagination ).toBe( false );

		// Simulate remote data fetch
		act( () => {
			result.current.onFetch(
				generateRemoteData( 5, {
					cursorNext: 'cursor-next',
					totalItems: 100,
				} )
			);
		} );

		expect( result.current.page ).toBe( 1 );
		expect( result.current.perPage ).toBeUndefined();
		expect( result.current.supportsPagination ).toBe( false );
		expect( result.current.totalItems ).toBeUndefined();
		expect( result.current.totalPages ).toBeUndefined();

		// Update page and per-page
		act( () => result.current.setPage( 2 ) );
		act( () => result.current.setPerPage( 50 ) );

		expect( result.current.page ).toBe( 1 );
		expect( result.current.perPage ).toBeUndefined();
		expect( result.current.supportsPagination ).toBe( false );
		expect( result.current.totalItems ).toBeUndefined();
		expect( result.current.totalPages ).toBeUndefined();
	} );

	it( 'supports initial page and per-page input', () => {
		const inputVariables: InputVariable[] = [];

		const { result } = renderHook( () =>
			usePaginationVariables( { initialPage: 11, initialPerPage: 27, inputVariables } )
		);

		expect( result.current.page ).toBe( 11 );
		expect( result.current.perPage ).toBe( 27 );
		expect( result.current.supportsPagination ).toBe( false );
	} );

	it( 'supports page-based pagination', () => {
		const inputVariables = [
			{
				required: false,
				slug: 'page',
				type: PAGINATION_PAGE_VARIABLE_TYPE,
			},
			{
				required: false,
				slug: 'perPage',
				type: PAGINATION_PER_PAGE_VARIABLE_TYPE,
			},
		];

		const { result } = renderHook( () => usePaginationVariables( { inputVariables } ) );

		expect( result.current.page ).toBe( 1 );
		expect( result.current.perPage ).toBeUndefined();
		expect( result.current.supportsPagination ).toBe( true );

		// Simulate remote data fetch
		act( () => {
			result.current.onFetch(
				generateRemoteData( 5, {
					totalItems: 100,
				} )
			);
		} );

		expect( result.current.page ).toBe( 1 );
		expect( result.current.perPage ).toBe( 5 );
		expect( result.current.supportsPagination ).toBe( true );
		expect( result.current.totalItems ).toBe( 100 );
		expect( result.current.totalPages ).toBe( 20 );

		// Update page
		act( () => result.current.setPage( 3 ) );

		expect( result.current.page ).toBe( 3 );
		expect( result.current.perPage ).toBe( 5 );
		expect( result.current.supportsPagination ).toBe( true );
		expect( result.current.totalItems ).toBe( 100 );
		expect( result.current.totalPages ).toBe( 20 );

		// Update per-page
		act( () => result.current.setPerPage( 25 ) );

		expect( result.current.page ).toBe( 3 );
		expect( result.current.perPage ).toBe( 25 );
		expect( result.current.supportsPagination ).toBe( true );
		expect( result.current.totalItems ).toBe( 100 );
		expect( result.current.totalPages ).toBe( 4 );
	} );

	it( 'should support cursor pagination', () => {
		const inputVariables = [
			{
				required: false,
				slug: 'next',
				type: PAGINATION_CURSOR_NEXT_VARIABLE_TYPE,
			},
			{
				required: false,
				slug: 'previous',
				type: PAGINATION_CURSOR_PREVIOUS_VARIABLE_TYPE,
			},
		];

		const { result } = renderHook( () => usePaginationVariables( { inputVariables } ) );

		expect( result.current.page ).toBe( 1 );
		expect( result.current.perPage ).toBeUndefined();
		expect( result.current.supportsPagination ).toBe( true );

		// Simulate remote data fetch
		act( () => {
			result.current.onFetch(
				generateRemoteData( 10, {
					cursorNext: 'test-next-cursor',
					totalItems: 115,
				} )
			);
		} );

		expect( result.current.page ).toBe( 1 );
		expect( result.current.perPage ).toBe( 10 );
		expect( result.current.paginationQueryInput ).toEqual( {} );
		expect( result.current.supportsPagination ).toBe( true );
		expect( result.current.totalItems ).toBe( 115 );
		expect( result.current.totalPages ).toBe( 12 );

		// Update page + 2
		act( () => result.current.setPage( 3 ) );

		expect( result.current.page ).toBe( 2 ); // can only go one at a time
		expect( result.current.perPage ).toBe( 10 );
		expect( result.current.paginationQueryInput ).toEqual( { next: 'test-next-cursor' } );
		expect( result.current.supportsPagination ).toBe( true );
		expect( result.current.totalItems ).toBe( 115 );
		expect( result.current.totalPages ).toBe( 12 );

		// Simulate remote data fetch
		act( () => {
			result.current.onFetch(
				generateRemoteData( 15, {
					cursorNext: 'test-next-cursor-2',
					cursorPrevious: 'test-previous-cursor',
					totalItems: 105,
				} )
			);
		} );

		// Update page - 1
		act( () => result.current.setPage( 1 ) );

		expect( result.current.page ).toBe( 1 );
		expect( result.current.perPage ).toBe( 15 );
		expect( result.current.paginationQueryInput ).toEqual( { previous: 'test-previous-cursor' } );
		expect( result.current.supportsPagination ).toBe( true );
		expect( result.current.totalItems ).toBe( 105 );
		expect( result.current.totalPages ).toBe( 7 );
	} );

	it( 'should support "simple" cursor pagination', () => {
		const inputVariables = [
			{
				required: false,
				slug: 'cursor',
				type: PAGINATION_CURSOR_VARIABLE_TYPE,
			},
		];

		const { result } = renderHook( () => usePaginationVariables( { inputVariables } ) );

		expect( result.current.page ).toBe( 1 );
		expect( result.current.perPage ).toBeUndefined();
		expect( result.current.supportsPagination ).toBe( true );

		// Simulate remote data fetch
		act( () => {
			result.current.onFetch(
				generateRemoteData( 10, {
					cursorNext: 'test-next-cursor',
					totalItems: 115,
				} )
			);
		} );

		expect( result.current.page ).toBe( 1 );
		expect( result.current.perPage ).toBe( 10 );
		expect( result.current.paginationQueryInput ).toEqual( {} );
		expect( result.current.supportsPagination ).toBe( true );
		expect( result.current.totalItems ).toBe( 115 );
		expect( result.current.totalPages ).toBe( 12 );

		// Update page + 2
		act( () => result.current.setPage( 3 ) );

		expect( result.current.page ).toBe( 2 ); // can only go one at a time
		expect( result.current.perPage ).toBe( 10 );
		expect( result.current.paginationQueryInput ).toEqual( { cursor: 'test-next-cursor' } );
		expect( result.current.supportsPagination ).toBe( true );
		expect( result.current.totalItems ).toBe( 115 );
		expect( result.current.totalPages ).toBe( 12 );

		// Simulate remote data fetch
		act( () => {
			result.current.onFetch(
				generateRemoteData( 15, {
					cursorNext: 'test-next-cursor-2',
					totalItems: 105,
				} )
			);
		} );

		// Update page - 1
		act( () => result.current.setPage( 1 ) );

		expect( result.current.page ).toBe( 1 );
		expect( result.current.perPage ).toBe( 15 );
		expect( result.current.paginationQueryInput ).toEqual( {} );
		expect( result.current.supportsPagination ).toBe( true );
		expect( result.current.totalItems ).toBe( 105 );
		expect( result.current.totalPages ).toBe( 7 );
	} );

	it( 'should support offset pagination', () => {
		const inputVariables = [
			{
				required: false,
				slug: 'offset',
				type: PAGINATION_OFFSET_VARIABLE_TYPE,
			},
			{
				required: false,
				slug: 'perPage',
				type: PAGINATION_PER_PAGE_VARIABLE_TYPE,
			},
		];

		const { result } = renderHook( () => usePaginationVariables( { inputVariables } ) );

		expect( result.current.page ).toBe( 1 );
		expect( result.current.perPage ).toBeUndefined();
		expect( result.current.supportsPagination ).toBe( true );

		// Simulate remote data fetch
		act( () => {
			result.current.onFetch(
				generateRemoteData( 10, {
					totalItems: 115,
				} )
			);
		} );

		expect( result.current.page ).toBe( 1 );
		expect( result.current.perPage ).toBe( 10 );
		expect( result.current.paginationQueryInput ).toEqual( {} );
		expect( result.current.supportsPagination ).toBe( true );
		expect( result.current.totalItems ).toBe( 115 );
		expect( result.current.totalPages ).toBe( 12 );

		// Update page + 2
		act( () => result.current.setPage( 3 ) );

		expect( result.current.page ).toBe( 3 );
		expect( result.current.perPage ).toBe( 10 );
		expect( result.current.paginationQueryInput ).toEqual( { offset: 20 } );
		expect( result.current.supportsPagination ).toBe( true );
		expect( result.current.totalItems ).toBe( 115 );
		expect( result.current.totalPages ).toBe( 12 );

		// Simulate remote data fetch
		act( () => {
			result.current.onFetch(
				generateRemoteData( 15, {
					totalItems: 105,
				} )
			);
		} );

		// Update page - 1
		act( () => result.current.setPage( 2 ) );

		expect( result.current.page ).toBe( 2 );
		expect( result.current.perPage ).toBe( 15 );
		expect( result.current.paginationQueryInput ).toEqual( { offset: 15 } );
		expect( result.current.supportsPagination ).toBe( true );
		expect( result.current.totalItems ).toBe( 105 );
		expect( result.current.totalPages ).toBe( 7 );
	} );
} );
