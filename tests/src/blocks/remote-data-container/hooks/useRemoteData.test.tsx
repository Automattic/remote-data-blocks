import { act, renderHook, waitFor } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';
import { beforeEach, expect, it, vi } from 'vitest';

import { useRemoteData } from '@/blocks/remote-data-container/hooks/useRemoteData';

vi.mock( '@wordpress/editor', () => ( { store: {} } ) );
vi.mock( '@wordpress/data', () => ( { select: () => ( { getCurrentPostId: () => 1 } ) } ) );
vi.mock( '@/utils/localized-block-data', () => ( {
	getRestUrl: () => '/test',
	getBlockConfig: () => ( {
		selectors: [ { query_key: 'list', inputs: [ { slug: 'page', type: 'ui:pagination_page' } ] } ],
	} ),
} ) );
vi.mock( '@wordpress/api-fetch', () => ( { default: vi.fn() } ) );

const fetchMock = vi.mocked( apiFetch );
const requests: {
	resolve: () => void;
	reject: ( error: Error ) => void;
}[] = [];
let instance = 0;

beforeEach( () => {
	requests.length = 0;
	fetchMock.mockReset().mockImplementation( options => {
		const { query_inputs, block_name } = options?.data as RemoteDataApiRequest;
		return new Promise( ( resolve, reject ) =>
			requests.push( {
				resolve: () => resolve( { status: 200, body: { query_inputs, block_name } } ),
				reject,
			} )
		);
	} );
} );

function mount( data?: RemoteData ) {
	const blockName = `test/list-${ instance++ }`;
	const update = vi.fn();
	const hook = renderHook( () =>
		useRemoteData( {
			blockName,
			queryKey: 'list',
			externallyManagedRemoteData: data,
			externallyManagedUpdateRemoteData: update,
		} )
	);
	return { ...hook, update };
}

function startRequests() {
	const hook = mount();
	for ( const page of [ 1, 2 ] ) {
		act( () => {
			void hook.result.current.fetch( [ { page } ] );
		} );
	}
	expect( requests ).toHaveLength( 2 );
	return hook;
}

it.each( [ 'success', 'error' ] )(
	'ignores an older %s while the latest request is pending',
	async outcome => {
		const { result, update } = startRequests();
		const [ older, latest ] = requests;
		await act( async () => {
			if ( outcome === 'success' ) {
				older!.resolve();
			} else {
				older!.reject( new Error( 'Old request failed' ) );
			}
		} );
		expect( result.current.loading ).toBe( true );
		expect( update ).not.toHaveBeenCalled();

		await act( async () => latest!.resolve() );
		expect( result.current.loading ).toBe( false );
		expect( update ).toHaveBeenCalledExactlyOnceWith(
			expect.objectContaining( { queryInputs: [ { page: 2 } ] } )
		);
	}
);

it( 'ignores a stale success after a newer result arrives', async () => {
	const { update } = startRequests();
	await act( async () => requests[ 1 ]!.resolve() );
	await act( async () => requests[ 0 ]!.resolve() );
	expect( update ).toHaveBeenCalledExactlyOnceWith(
		expect.objectContaining( { queryInputs: [ { page: 2 } ] } )
	);
} );

it.each( [ 'reset', 'unmount' ] )( 'invalidates pending work on %s', async action => {
	const { result, unmount, update } = startRequests();
	if ( action === 'reset' ) {
		act( () => result.current.reset() );
		expect( update ).toHaveBeenCalledExactlyOnceWith( undefined );
	} else {
		unmount();
	}
	update.mockClear();
	await act( async () => requests.forEach( request => request.resolve() ) );
	expect( update ).not.toHaveBeenCalled();
} );

it( 'keeps the last successful query inputs intact while a new page loads', async () => {
	const queryInputs = [ { page: 1 } ];
	const { result } = mount( {
		blockName: 'test/list',
		metadata: {},
		queryInputs,
		resultId: 'first',
		results: [],
		pagination: {
			type: 'PAGE',
			input_variable_targets: { page: 'page' },
			input_variables: { next_page: { page: 2 } },
		},
	} );
	act( () => result.current.setPage( 2 ) );
	await waitFor( () => expect( requests ).toHaveLength( 2 ) );
	expect( queryInputs ).toEqual( [ { page: 1 } ] );
} );
