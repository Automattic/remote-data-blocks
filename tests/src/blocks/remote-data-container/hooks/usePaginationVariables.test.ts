import { act, renderHook } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import {
	usePaginationVariables,
	UsePaginationVariablesInput,
} from '@/blocks/remote-data-container/hooks/usePaginationVariables';

describe( 'usePaginationVariables', () => {
	it( 'provides static variables when pagination is not supported', () => {
		const { result, rerender } = renderHook(
			( props: UsePaginationVariablesInput ) => usePaginationVariables( props ),
			{ initialProps: {} }
		);

		expect( result.current.page ).toBe( 1 );
		expect( result.current.perPage ).toBeUndefined();
		expect( result.current.supportsPagination ).toBe( false );

		// Simulate remote data fetch
		rerender( {
			paginationData: {
				input_variables: {},
				input_variable_targets: {},
				type: 'NONE',
			},
		} );

		expect( result.current.page ).toBe( 1 );
		expect( result.current.perPage ).toBeUndefined();
		expect( result.current.paginationQueryInput ).toBeUndefined();
		expect( result.current.supportsPagination ).toBe( false );
		expect( result.current.totalItems ).toBeUndefined();
		expect( result.current.totalPages ).toBeUndefined();

		// Update page and per-page
		act( () => result.current.setPage( 2 ) );
		act( () => result.current.setPerPage( 50 ) );

		expect( result.current.page ).toBe( 1 );
		expect( result.current.perPage ).toBeUndefined();
		expect( result.current.paginationQueryInput ).toBeUndefined();
		expect( result.current.supportsPagination ).toBe( false );
		expect( result.current.totalItems ).toBeUndefined();
		expect( result.current.totalPages ).toBeUndefined();
	} );

	it( 'supports initial page and per-page input', () => {
		const { result } = renderHook(
			( props: UsePaginationVariablesInput ) => usePaginationVariables( props ),
			{ initialProps: { initialPage: 11, initialPerPage: 27 } }
		);

		expect( result.current.page ).toBe( 11 );
		expect( result.current.perPage ).toBe( 27 );
		expect( result.current.paginationQueryInput ).toBeUndefined();
		expect( result.current.supportsPagination ).toBe( false );
	} );

	it( 'supports page-based pagination', () => {
		const { result, rerender } = renderHook(
			( props: UsePaginationVariablesInput ) => usePaginationVariables( props ),
			{ initialProps: {} }
		);

		expect( result.current.page ).toBe( 1 );
		expect( result.current.perPage ).toBeUndefined();
		expect( result.current.paginationQueryInput ).toBeUndefined();

		// Simulate remote data fetch
		rerender( {
			paginationData: {
				input_variables: {},
				input_variable_targets: {
					page: 'item',
					per_page: 'perPage',
				},
				per_page: 5,
				total_items: 100,
				type: 'PAGE',
			},
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
		expect( result.current.paginationQueryInput ).toEqual( { item: 3 } );
		expect( result.current.supportsPagination ).toBe( true );
		expect( result.current.totalItems ).toBe( 100 );
		expect( result.current.totalPages ).toBe( 20 );

		// Update per-page
		act( () => result.current.setPerPage( 25 ) );

		expect( result.current.page ).toBe( 3 );
		expect( result.current.perPage ).toBe( 25 );
		expect( result.current.paginationQueryInput ).toEqual( { item: 3, perPage: 25 } );
		expect( result.current.supportsPagination ).toBe( true );
		expect( result.current.totalItems ).toBe( 100 );
		expect( result.current.totalPages ).toBe( 4 );
	} );

	it( 'should support cursor pagination', () => {
		const { result, rerender } = renderHook(
			( props: UsePaginationVariablesInput ) => usePaginationVariables( props ),
			{ initialProps: {} }
		);

		expect( result.current.page ).toBe( 1 );
		expect( result.current.perPage ).toBeUndefined();
		expect( result.current.paginationQueryInput ).toBeUndefined();

		// Simulate remote data fetch
		rerender( {
			paginationData: {
				input_variables: {
					next_page: {
						next: 'test-next-cursor',
					},
					previous_page: {},
				},
				input_variable_targets: {},
				per_page: 10,
				total_items: 115,
				type: 'CURSOR',
			},
		} );

		expect( result.current.page ).toBe( 1 );
		expect( result.current.perPage ).toBe( 10 );
		expect( result.current.paginationQueryInput ).toBeUndefined();
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
		rerender( {
			paginationData: {
				input_variables: {
					next_page: {
						next: 'test-next-cursor-2',
					},
					previous_page: {
						previous: 'test-previous-cursor',
					},
				},
				input_variable_targets: {},
				per_page: 15,
				total_items: 105,
				type: 'CURSOR',
			},
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

	it.each( [ 'CURSOR', 'CURSOR_SIMPLE' ] as const )(
		'resets %s pagination directly from a later page',
		type => {
			const nextCursorSlug = type === 'CURSOR' ? 'cursor_next' : 'cursor';
			const cursorSlugs = [ nextCursorSlug, ...( type === 'CURSOR' ? [ 'cursor_previous' ] : [] ) ];
			const paginationData: RemoteDataPagination = {
				input_variables: { next_page: { [ nextCursorSlug ]: 'old-cursor' } },
				input_variable_targets: { per_page: 'page_size' },
				per_page: 5,
				type,
			};
			const { result } = renderHook( () =>
				usePaginationVariables( {
					paginationData,
					inputVariables: [
						...cursorSlugs.map( slug => ( {
							slug,
							type: `ui:pagination_${ slug }`,
							required: false,
						} ) ),
						{ slug: 'page_size', type: 'ui:pagination_per_page', required: false },
					],
				} )
			);

			act( () => result.current.setPage( 2 ) );
			act( () => result.current.setPage( 3 ) );
			expect( result.current.page ).toBe( 3 );
			expect( result.current.paginationQueryInput ).toEqual( {
				[ nextCursorSlug ]: 'old-cursor',
			} );

			act( () => result.current.resetPage() );
			expect( result.current.page ).toBe( 1 );
			expect( result.current.paginationQueryInput ).toEqual( {
				...Object.fromEntries( cursorSlugs.map( slug => [ slug, undefined ] ) ),
				page_size: 5,
			} );
			expect(
				JSON.stringify( {
					...Object.fromEntries( cursorSlugs.map( slug => [ slug, 'old-cursor' ] ) ),
					...result.current.paginationQueryInput,
				} )
			).toBe( '{"page_size":5}' );
		}
	);

	it( 'should support "simple" cursor pagination', () => {
		const { result, rerender } = renderHook(
			( props: UsePaginationVariablesInput ) => usePaginationVariables( props ),
			{ initialProps: {} }
		);

		expect( result.current.page ).toBe( 1 );
		expect( result.current.perPage ).toBeUndefined();
		expect( result.current.paginationQueryInput ).toBeUndefined();

		// Simulate remote data fetch
		rerender( {
			paginationData: {
				input_variables: {
					next_page: {
						cursor: 'test-next-cursor',
					},
				},
				input_variable_targets: {},
				per_page: 10,
				total_items: 115,
				type: 'CURSOR_SIMPLE',
			},
		} );

		expect( result.current.page ).toBe( 1 );
		expect( result.current.perPage ).toBe( 10 );
		expect( result.current.paginationQueryInput ).toBeUndefined();
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
		rerender( {
			paginationData: {
				input_variables: {},
				input_variable_targets: {},
				per_page: 15,
				total_items: 105,
				type: 'CURSOR_SIMPLE',
			},
		} );

		// Update page - 1
		act( () => result.current.setPage( 1 ) );

		expect( result.current.page ).toBe( 1 );
		expect( result.current.perPage ).toBe( 15 );
		expect( result.current.paginationQueryInput ).toBeUndefined();
		expect( result.current.supportsPagination ).toBe( true );
		expect( result.current.totalItems ).toBe( 105 );
		expect( result.current.totalPages ).toBe( 7 );
	} );

	it( 'should support offset pagination', () => {
		const { result, rerender } = renderHook(
			( props: UsePaginationVariablesInput ) => usePaginationVariables( props ),
			{ initialProps: {} }
		);

		expect( result.current.page ).toBe( 1 );
		expect( result.current.perPage ).toBeUndefined();
		expect( result.current.paginationQueryInput ).toBeUndefined();

		// Simulate remote data fetch
		rerender( {
			paginationData: {
				input_variables: {},
				input_variable_targets: {
					offset: 'limit',
					per_page: 'perPage',
				},
				per_page: 10,
				total_items: 115,
				type: 'OFFSET',
			},
		} );

		expect( result.current.page ).toBe( 1 );
		expect( result.current.perPage ).toBe( 10 );
		expect( result.current.paginationQueryInput ).toBeUndefined();
		expect( result.current.supportsPagination ).toBe( true );
		expect( result.current.totalItems ).toBe( 115 );
		expect( result.current.totalPages ).toBe( 12 );

		// Update page + 2
		act( () => result.current.setPage( 3 ) );

		expect( result.current.page ).toBe( 3 );
		expect( result.current.perPage ).toBe( 10 );
		expect( result.current.paginationQueryInput ).toEqual( { limit: 20 } );
		expect( result.current.supportsPagination ).toBe( true );
		expect( result.current.totalItems ).toBe( 115 );
		expect( result.current.totalPages ).toBe( 12 );

		// Simulate remote data fetch
		rerender( {
			paginationData: {
				input_variables: {},
				input_variable_targets: {
					offset: 'limit',
					per_page: 'perPage',
				},
				per_page: 15,
				total_items: 105,
				type: 'OFFSET',
			},
		} );

		// Update page - 1
		act( () => result.current.setPage( 2 ) );

		expect( result.current.page ).toBe( 2 );
		expect( result.current.perPage ).toBe( 15 );
		expect( result.current.paginationQueryInput ).toEqual( { limit: 15 } );
		expect( result.current.supportsPagination ).toBe( true );
		expect( result.current.totalItems ).toBe( 105 );
		expect( result.current.totalPages ).toBe( 7 );
	} );
} );
