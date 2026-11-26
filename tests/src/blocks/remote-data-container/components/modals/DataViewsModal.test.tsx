import { act, render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import apiFetch from '@wordpress/api-fetch';
import { afterEach, describe, expect, it, vi } from 'vitest';

import '@/dataviews';
import { EditErrorBoundary } from '@/blocks/remote-data-container/components/EditErrorBoundary';
import { DataViewsModal } from '@/blocks/remote-data-container/components/modals/DataViewsModal';

vi.mock( '@wordpress/editor', () => ( { store: {} } ) );
vi.mock( '@wordpress/data', async importOriginal => ( {
	...( await importOriginal< typeof import('@wordpress/data') >() ),
	select: () => ( { getCurrentPostId: () => 1 } ),
} ) );
vi.mock( '@wordpress/api-fetch', () => ( { default: vi.fn() } ) );
vi.mock( '@/blocks/remote-data-container/utils/tracks', () => ( { sendTracksEvent: vi.fn() } ) );
vi.mock( '@/utils/localized-block-data', () => ( {
	getRestUrl: () => '/test',
	getBlockDataSourceType: () => 'test',
	getBlockConfig: ( blockName: string ) => ( {
		selectors: [
			{
				query_key: 'list',
				inputs: [
					{
						slug: 'search',
						type: 'ui:search_input',
						required: blockName.startsWith( 'test/required' ),
					},
					...getPaginationInputs( blockName ),
				],
			},
		],
	} ),
} ) );

function getPaginationInputs( blockName: string ) {
	if ( blockName.includes( 'simple-cursor' ) ) {
		return [
			{ slug: 'cursor', type: 'ui:pagination_cursor' },
			{ slug: 'limit', type: 'ui:pagination_per_page' },
		];
	}
	if ( blockName.includes( 'directional-cursor' ) ) {
		return [
			{ slug: 'after', type: 'ui:pagination_cursor_next' },
			{ slug: 'before', type: 'ui:pagination_cursor_previous' },
		];
	}
	return [ { slug: 'page', type: 'ui:pagination_page' } ];
}

const fetchMock = vi.mocked( apiFetch );
let instance = 0;

function lastQueryInput() {
	const request = fetchMock.mock.lastCall?.[ 0 ]?.data as RemoteDataApiRequest | undefined;
	return request?.query_inputs[ 0 ];
}

async function openModal( required: boolean, mode = 'page' ) {
	const user = userEvent.setup();
	const onSelect = vi.fn();
	const blockName = `test/${ required ? 'required' : 'optional' }-${ mode }-${ instance++ }`;
	render(
		<EditErrorBoundary blockTitle="Test chooser">
			<DataViewsModal
				blockName={ blockName }
				onSelect={ onSelect }
				queryKey="list"
				title="Chooser"
			/>
		</EditErrorBoundary>
	);
	await user.click( screen.getByRole( 'button', { name: 'Select items' } ) );
	const modal = within( screen.getByRole( 'dialog', { name: 'Chooser' } ) );
	return { user, modal, onSelect };
}

function rowResponse(
	request: RemoteDataApiRequest,
	title: string,
	pagination: RemoteDataPagination
) {
	return {
		status: 200,
		body: {
			block_name: request.block_name,
			query_key: request.query_key,
			query_inputs: request.query_inputs,
			results: [
				{ uuid: title, result: { title: { name: 'Title', type: 'title', value: title } } },
			],
			pagination,
		},
	};
}

function respondWithRows() {
	fetchMock.mockImplementation( options => {
		const request = options?.data as RemoteDataApiRequest;
		const { page = 1, search } = request.query_inputs[ 0 ] ?? {};
		const title =
			typeof search === 'string' && search ? `${ search } result` : `Row ${ Number( page ) }`;
		return Promise.resolve(
			rowResponse( request, title, {
				type: 'PAGE',
				per_page: 1,
				total_items: 2,
				input_variable_targets: { page: 'page' },
				input_variables:
					Number( page ) === 1 ? { next_page: { page: 2 } } : { previous_page: { page: 1 } },
			} )
		);
	} );
}

function respondWithCursorRows( mode: 'simple-cursor' | 'directional-cursor' ) {
	fetchMock.mockImplementation( options => {
		const request = options?.data as RemoteDataApiRequest;
		const { cursor, after, before, limit = 1 } = request.query_inputs[ 0 ] ?? {};
		if ( after && before ) {
			return Promise.reject( new Error( 'Pagination requests must use one cursor direction' ) );
		}

		const number = Number( cursor ?? after ?? before ?? 1 );
		const isSimple = mode === 'simple-cursor';
		return Promise.resolve(
			rowResponse( request, `Row ${ number }`, {
				type: isSimple ? 'CURSOR_SIMPLE' : 'CURSOR',
				per_page: Number( limit ),
				input_variable_targets: isSimple ? { per_page: 'limit' } : {},
				input_variables: {
					next_page:
						number < 3 ? { [ isSimple ? 'cursor' : 'after' ]: String( number + 1 ) } : undefined,
					previous_page: ! isSimple && number > 1 ? { before: String( number - 1 ) } : undefined,
				},
			} )
		);
	} );
}

function deferFailure() {
	let failRequest!: ( error: Error ) => void;
	fetchMock.mockImplementationOnce(
		() => new Promise( ( _resolve, reject ) => ( failRequest = reject ) )
	);
	return ( message: string ) => act( async () => failRequest( new Error( message ) ) );
}

describe( 'DataViewsModal', () => {
	afterEach( () => {
		fetchMock.mockReset();
		vi.restoreAllMocks();
	} );

	it( 'discards the pending first required search when it is cleared', async () => {
		vi.spyOn( console, 'error' ).mockImplementation( () => {} );
		respondWithRows();
		const failRequest = deferFailure();
		const { user, modal } = await openModal( true );
		const search = modal.getByRole( 'searchbox', { name: 'Search' } );
		expect( fetchMock ).not.toHaveBeenCalled();
		await user.type( search, 'Herb' );
		await waitFor( () => expect( lastQueryInput()?.search ).toBe( 'Herb' ) );
		await user.clear( search );
		await failRequest( 'Cancelled search failed' );

		expect( screen.getByRole( 'dialog', { name: 'Chooser' } ) ).toBeVisible();
		expect( modal.getByRole( 'button', { name: 'Select items' } ) ).toBeDisabled();
		expect( fetchMock ).toHaveBeenCalledTimes( 1 );
		await user.type( search, 'Fern' );
		expect( await modal.findByText( 'Fern result' ) ).toBeVisible();
		expect( lastQueryInput()?.search ).toBe( 'Fern' );
	} );

	it( 'keeps the chosen page size when restarting a required search', async () => {
		respondWithCursorRows( 'simple-cursor' );
		const { user, modal } = await openModal( true, 'simple-cursor' );
		const search = modal.getByRole( 'searchbox', { name: 'Search' } );
		await user.type( search, 'Herb' );
		const pageSize = await modal.findByRole( 'combobox', { name: 'Items per page' } );
		await user.selectOptions( pageSize, '20' );
		await waitFor( () => expect( lastQueryInput()?.limit ).toBe( 20 ) );
		await user.clear( search );
		await waitFor( () =>
			expect( modal.queryByRole( 'combobox', { name: 'Items per page' } ) ).toBeNull()
		);
		await user.type( search, 'Fern' );
		await waitFor( () => expect( lastQueryInput()?.search ).toBe( 'Fern' ) );
		expect( lastQueryInput()?.limit ).toBe( 20 );
	} );

	it( 'reports a failure from the current required search', async () => {
		vi.spyOn( console, 'error' ).mockImplementation( () => {} );
		const failRequest = deferFailure();
		const { user, modal } = await openModal( true );
		await user.type( modal.getByRole( 'searchbox', { name: 'Search' } ), 'Herb' );
		await waitFor( () => expect( fetchMock ).toHaveBeenCalledTimes( 1 ) );
		await failRequest( 'Current search failed' );
		await user.click( screen.getByRole( 'button', { name: 'Show error details' } ) );
		expect( screen.getByText( 'Current search failed' ) ).toBeVisible();
	} );

	it( 'discards a draft search when the chooser closes before the debounce', async () => {
		vi.spyOn( console, 'error' ).mockImplementation( () => {} );
		fetchMock.mockRejectedValue( new Error( 'Closed search failed' ) );
		const { user, modal } = await openModal( true );
		await user.type( modal.getByRole( 'searchbox', { name: 'Search' } ), 'Herb' );
		await user.keyboard( '{Escape}' );
		expect( screen.queryByRole( 'dialog', { name: 'Chooser' } ) ).toBeNull();
		await act( () => new Promise( resolve => setTimeout( resolve, 550 ) ) );

		expect( screen.queryByRole( 'button', { name: 'Try again' } ) ).toBeNull();
		expect( fetchMock ).not.toHaveBeenCalled();
		await user.click( screen.getByRole( 'button', { name: 'Select items' } ) );
		expect( screen.getByRole( 'searchbox', { name: 'Search' } ) ).toHaveValue( '' );
	} );

	it( 'ignores an active search failure after closing the chooser', async () => {
		vi.spyOn( console, 'error' ).mockImplementation( () => {} );
		const failRequest = deferFailure();
		const { user, modal } = await openModal( true );
		await user.type( modal.getByRole( 'searchbox', { name: 'Search' } ), 'Herb' );
		await waitFor( () => expect( lastQueryInput()?.search ).toBe( 'Herb' ) );
		await user.keyboard( '{Escape}' );
		expect( screen.queryByRole( 'dialog', { name: 'Chooser' } ) ).toBeNull();
		await failRequest( 'Closed search failed' );

		expect( screen.queryByRole( 'button', { name: 'Try again' } ) ).toBeNull();
		expect( screen.getByRole( 'button', { name: 'Select items' } ) ).toBeEnabled();
	} );

	it( 'requests the first page with the new term when editing a search on page two', async () => {
		vi.spyOn( console, 'error' ).mockImplementation( () => {} );
		respondWithRows();
		const { user, modal } = await openModal( true );
		const search = modal.getByRole( 'searchbox', { name: 'Search' } );
		await user.type( search, 'Herb' );
		expect( await modal.findByText( 'Herb result' ) ).toBeVisible();
		await user.click( modal.getByRole( 'button', { name: 'Next page' } ) );
		await waitFor( () => expect( lastQueryInput() ).toMatchObject( { page: 2, search: 'Herb' } ) );
		expect( await modal.findByText( 'Herb result' ) ).toBeVisible();
		fetchMock.mockClear();
		const respond = fetchMock.getMockImplementation()!;
		fetchMock.mockImplementation( options =>
			lastQueryInput()?.search === 'Herb' && lastQueryInput()?.page === 1
				? Promise.reject( new Error( 'Old term fetched during page reset' ) )
				: respond( options )
		);

		await user.type( search, 's' );
		expect( search ).toHaveValue( 'Herbs' );
		await waitFor( () => expect( lastQueryInput() ).toMatchObject( { page: 1, search: 'Herbs' } ) );
		expect( await modal.findByText( 'Herbs result' ) ).toBeVisible();
		expect( fetchMock ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'ignores an older page failure before the next search starts', async () => {
		vi.spyOn( console, 'error' ).mockImplementation( () => {} );
		respondWithRows();
		const { user, modal } = await openModal( false );
		expect( await modal.findByText( 'Row 1' ) ).toBeVisible();
		const failRequest = deferFailure();
		await user.click( modal.getByRole( 'button', { name: 'Next page' } ) );
		await waitFor( () => expect( lastQueryInput()?.page ).toBe( 2 ) );
		await user.type( modal.getByRole( 'searchbox', { name: 'Search' } ), 'Fern' );
		await failRequest( 'Old page failed' );

		expect( screen.getByRole( 'dialog', { name: 'Chooser' } ) ).toBeVisible();
		expect( await modal.findByText( 'Fern result' ) ).toBeVisible();
		expect( lastQueryInput() ).toMatchObject( { page: 1, search: 'Fern' } );
	} );

	it( 'fetches all rows when an optional search is cleared', async () => {
		respondWithRows();
		const { user, modal } = await openModal( false );
		const search = modal.getByRole( 'searchbox', { name: 'Search' } );
		expect( await modal.findByText( 'Row 1' ) ).toBeVisible();
		await user.type( search, 'Herb' );
		expect( await modal.findByText( 'Herb result' ) ).toBeVisible();
		await user.clear( search );
		expect( await modal.findByText( 'Row 1' ) ).toBeVisible();
		expect( lastQueryInput() ).toMatchObject( { page: 1, search: '' } );
	} );

	it( 'sends only the requested cursor when navigating backwards', async () => {
		respondWithCursorRows( 'directional-cursor' );
		const { modal, user } = await openModal( false, 'directional-cursor' );
		await modal.findByRole( 'row', { name: /Row 1/ } );
		await user.click( modal.getByRole( 'button', { name: 'Next page' } ) );
		await modal.findByRole( 'row', { name: /Row 2/ } );
		await user.click( modal.getByRole( 'button', { name: 'Previous page' } ) );
		await modal.findByRole( 'row', { name: /Row 1/ } );
	} );

	it( 'clears selections from every page via the footer', async () => {
		respondWithRows();
		const { user, modal, onSelect } = await openModal( false );
		const checkbox = async ( name: string ) =>
			within( await modal.findByRole( 'row', { name: new RegExp( name ) } ) ).getByRole(
				'checkbox'
			);
		await user.click( await checkbox( 'Row 1' ) );
		await user.click( modal.getByRole( 'button', { name: 'Next page' } ) );
		const secondRow = await checkbox( 'Row 2' );
		await user.click( secondRow );
		await user.click( secondRow );
		const submit = modal.getByRole( 'button', { name: 'Select items' } );
		expect( submit ).toBeEnabled();
		await user.click( secondRow );
		const clear = modal.getByRole( 'button', { name: 'Clear selection' } );
		await user.click( clear );
		await user.click( submit );
		expect( onSelect ).not.toHaveBeenCalled();
		expect( submit ).toBeDisabled();
		expect( clear ).toBeDisabled();
		expect( secondRow ).not.toBeChecked();
		await user.click( modal.getByRole( 'button', { name: 'Previous page' } ) );
		expect( await checkbox( 'Row 1' ) ).not.toBeChecked();
	} );
} );
