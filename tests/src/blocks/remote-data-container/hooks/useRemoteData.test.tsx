import { act, renderHook, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { EditErrorBoundary } from '@/blocks/remote-data-container/components/EditErrorBoundary';
import { useRemoteData } from '@/blocks/remote-data-container/hooks/useRemoteData';

const requests = vi.hoisted( () => ( {
	defer: false,
	pending: [] as { search: string; resolve: () => void; reject: ( error: Error ) => void }[],
} ) );
vi.mock( '@wordpress/editor', () => ( { store: {} } ) );
vi.mock( '@wordpress/data', () => ( { select: () => ( { getCurrentPostId: () => 1 } ) } ) );
vi.mock( '@/utils/localized-block-data', () => ( {
	getRestUrl: () => '/test',
	getBlockConfig: () => ( {
		selectors: [
			{
				query_key: 'search',
				inputs: [
					{ slug: 'search', type: 'ui:search_input' },
					{ slug: 'per_page', type: 'ui:pagination_per_page' },
					{ slug: 'cursor', type: 'ui:pagination_cursor' },
				],
			},
		],
	} ),
} ) );
vi.mock( '@/blocks/remote-data-container/components/placeholders/PlaceholderError', () => ( {
	PlaceholderError: ( { error }: { error: Error } ) => <div role="alert">{ error.message }</div>,
} ) );
vi.mock( '@wordpress/api-fetch', () => ( {
	default: vi.fn( async ( { data }: { data: RemoteDataApiRequest } ) => {
		const input = {
			search: '',
			per_page: 10,
			cursor: '',
			...JSON.parse( JSON.stringify( data.query_inputs[ 0 ] ) ),
		};
		let title = input.cursor ? 'Tree 11' : 'Tree 01';
		if ( input.search ) {
			title = 'Herb 02';
		}
		const response = {
			status: 200,
			body: {
				block_name: data.block_name,
				query_inputs: [ input ],
				query_key: 'search',
				result_id: title,
				results: [
					{ uuid: 'row', result: { title: { name: 'Title', type: 'title', value: title } } },
				],
				pagination: {
					type: 'CURSOR_SIMPLE',
					per_page: input.per_page,
					total_items: 50,
					input_variable_targets: { per_page: 'per_page' },
					input_variables: {
						next_page: { cursor: String( Number( input.cursor || 0 ) + input.per_page ) },
					},
				},
			},
		};
		if ( requests.defer ) {
			return new Promise( ( resolve, reject ) =>
				requests.pending.push( {
					search: input.search,
					resolve: () => resolve( response ),
					reject,
				} )
			);
		}
		return response;
	} ),
} ) );

let instance = 0;
function mount( onSuccess = vi.fn() ) {
	const blockName = `test/cursor-${ instance++ }`;
	return renderHook(
		() => useRemoteData( { blockName, queryKey: 'search', fetchOnMount: true, onSuccess } ),
		{
			wrapper: ( { children } ) => (
				<EditErrorBoundary blockTitle="Test">{ children }</EditErrorBoundary>
			),
		}
	);
}

async function startSearchFromPageTwo() {
	const onSuccess = vi.fn();
	const hook = mount( onSuccess );
	const title = () => hook.result.current.data?.results?.[ 0 ]?.result.title?.value;
	await waitFor( () => expect( title() ).toBe( 'Tree 01' ) );
	act( () => hook.result.current.setPage( 2 ) );
	await waitFor( () => expect( title() ).toBe( 'Tree 11' ) );
	requests.defer = true;
	act( () => {
		hook.result.current.resetPage();
		hook.result.current.setSearchInput( 'Herb' );
	} );
	await waitFor( () =>
		expect( requests.pending.some( request => request.search === '' ) ).toBe( true )
	);
	await waitFor( () =>
		expect( requests.pending.some( request => request.search === 'Herb' ) ).toBe( true )
	);
	return {
		...hook,
		title,
		onSuccess,
		older: requests.pending.find( request => request.search === '' )!,
		latest: requests.pending.find( request => request.search === 'Herb' )!,
	};
}

describe( 'remote query completion order', () => {
	beforeEach( () => {
		requests.defer = false;
		requests.pending = [];
	} );

	it.each( [ 'success', 'error' ] )(
		'ignores an older %s after the filtered result arrives',
		async outcome => {
			const { result, title, onSuccess, older, latest } = await startSearchFromPageTwo();
			await act( async () => latest.resolve() );
			expect( title() ).toBe( 'Herb 02' );
			const completed = onSuccess.mock.calls.length;
			await act( async () =>
				outcome === 'success' ? older.resolve() : older.reject( new Error( 'Old request failed' ) )
			);
			expect( title() ).toBe( 'Herb 02' );
			expect( result.current.loading ).toBe( false );
			expect( screen.queryByRole( 'alert' ) ).toBeNull();
			expect( onSuccess ).toHaveBeenCalledTimes( completed );
		}
	);

	it( 'keeps loading while the newer request remains pending', async () => {
		const { result, older, latest } = await startSearchFromPageTwo();
		await act( async () => older.resolve() );
		expect( result.current.loading ).toBe( true );
		await act( async () => latest.resolve() );
		expect( result.current.loading ).toBe( false );
	} );

	it( 'reports the current request failure', async () => {
		vi.spyOn( console, 'error' ).mockImplementation( () => {} );
		const { latest } = await startSearchFromPageTwo();
		await act( async () => latest.reject( new Error( 'Latest request failed' ) ) );
		expect( screen.getByRole( 'alert' ) ).toHaveTextContent( 'Latest request failed' );
		vi.restoreAllMocks();
	} );

	it( 'keeps the last successful query inputs intact while a new page loads', async () => {
		const { result } = mount();
		await waitFor( () => expect( result.current.data ).toBeDefined() );
		const savedInputs = structuredClone( result.current.data?.queryInputs );
		requests.defer = true;
		act( () => result.current.setPage( 2 ) );
		await waitFor( () => expect( requests.pending ).toHaveLength( 1 ) );
		expect( result.current.data?.queryInputs ).toEqual( savedInputs );
	} );

	it.each( [ 'reset', 'unmount' ] )( 'invalidates pending work on %s', async action => {
		const { result, unmount, onSuccess, older, latest } = await startSearchFromPageTwo();
		const completed = onSuccess.mock.calls.length;
		if ( action === 'reset' ) {
			act( () => result.current.reset() );
		} else {
			unmount();
		}
		const loadingAfterReset = result.current.loading;
		await act( async () => {
			older.resolve();
			latest.resolve();
		} );
		expect( onSuccess ).toHaveBeenCalledTimes( completed );
		if ( action === 'reset' ) {
			expect( result.current.data ).toBeUndefined();
			expect( result.current.loading ).toBe( loadingAfterReset );
		}
	} );
} );
