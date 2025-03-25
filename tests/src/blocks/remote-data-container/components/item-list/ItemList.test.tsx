import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { describe, expect, it, vi } from 'vitest';

import '@/dataviews'; // Required to load data views global

import {
	ItemList,
	ItemListProps,
} from '@/blocks/remote-data-container/components/item-list/ItemList';

const mockRemoteData: RemoteData = {
	blockName: 'Test Block',
	metadata: {},
	queryInputs: [],
	resultId: 'test-result',
	results: [
		{
			uuid: 'violets',
			result: { title: { name: 'Title', type: 'title', value: 'Violets' } },
		},
		{
			uuid: 'crimson-clover',
			result: { title: { name: 'Title', type: 'title', value: 'Crimson Clover' } },
		},
		{
			uuid: 'poppy',
			result: { title: { name: 'Title', type: 'title', value: 'Poppy' } },
		},
	],
};

const defaultProps = {
	availableBindings: {
		title: {
			name: 'Title',
			type: 'title',
			required: false,
		},
	},
	blockName: mockRemoteData.blockName,
	hasNextPage: false,
	idField: 'id',
	loading: false,
	page: 1,
	results: mockRemoteData.results,
	searchInput: '',
	selectionIds: [],
	setPage: () => {},
	setPerPage: () => {},
	setSearchInput: () => {},
	setSelectionIds: () => {},
	supportsBulk: false,
	supportsSearch: false,
};

const ItemListComponent = ( props: ItemListProps ) => {
	const [ selection, setSelection ] = useState< RemoteDataApiResult[] >( [] );
	const selectionIds = selection.map( item => item.uuid );
	function setSelectionIds( uuids: string[] ): void {
		const newSelection = uuids
			.map( uuid => props.results?.find( result => result.uuid === uuid ) )
			.filter( ( item ): item is RemoteDataApiResult => item !== undefined );
		setSelection( newSelection );
	}
	return (
		<>
			<ItemList
				{ ...props }
				selectionIds={ selectionIds }
				setSelectionIds={ setSelectionIds }
				onSelect={ props.onSelect }
			/>
			<Button onClick={ () => setSelectionIds( [] ) }>Cancel</Button>
			<Button onClick={ () => props.onSelect?.( selection ) }>Save</Button>
		</>
	);
};

describe( 'ItemList', () => {
	it( 'should render rows when there are results', () => {
		const onSelect = vi.fn();
		render( <ItemListComponent { ...defaultProps } onSelect={ onSelect } /> );

		// Field should be visible
		expect( screen.getByRole( 'button', { name: 'Title' } ) ).toBeVisible();

		// Results should be visible
		expect( screen.getByText( 'Violets' ) ).toBeVisible();
		expect( screen.getByText( 'Crimson Clover' ) ).toBeVisible();
		expect( screen.getByText( 'Poppy' ) ).toBeVisible();
	} );

	it( 'should allow selection of a specific item', async () => {
		const onSelect = vi.fn();
		const user = userEvent.setup();

		render( <ItemListComponent { ...defaultProps } onSelect={ onSelect } /> );

		await user.click(
			within( screen.getByRole( 'row', { name: /Poppy/i } ) ).getByRole( 'button', {
				name: 'Choose',
			} )
		);

		// Verify onSelect was called with the correct item
		expect( onSelect ).toHaveBeenCalledWith(
			expect.arrayContaining( [
				{
					result: {
						title: {
							name: 'Title',
							type: 'title',
							value: 'Poppy',
						},
					},
					uuid: 'poppy',
				},
			] )
		);
	} );

	it( 'should allow bulk selection of items', async () => {
		const onSelect = vi.fn();
		const user = userEvent.setup();

		render( <ItemListComponent { ...defaultProps } onSelect={ onSelect } /> );

		// Get checkboxes for each item
		const violetsCheckbox = within( screen.getByRole( 'row', { name: /Violets/i } ) ).getByRole(
			'checkbox'
		);
		const poppyCheckbox = within( screen.getByRole( 'row', { name: /Poppy/i } ) ).getByRole(
			'checkbox'
		);

		// Select two items
		await user.click( violetsCheckbox );
		await user.click( poppyCheckbox );

		// Click the Save button
		const saveButton = screen.getByRole( 'button', { name: 'Save' } );
		await user.click( saveButton );

		// Verify onSelect was called with the correct items
		expect( onSelect ).toHaveBeenCalledWith(
			expect.arrayContaining( [
				{
					result: {
						title: {
							name: 'Title',
							type: 'title',
							value: 'Violets',
						},
					},
					uuid: 'violets',
				},
				{
					result: {
						title: {
							name: 'Title',
							type: 'title',
							value: 'Poppy',
						},
					},
					uuid: 'poppy',
				},
			] )
		);
	} );

	it( 'should allow deselection of items', async () => {
		const onSelect = vi.fn();
		const user = userEvent.setup();

		render( <ItemListComponent { ...defaultProps } onSelect={ onSelect } /> );

		// Get checkboxes for each item
		const violetsCheckbox = within( screen.getByRole( 'row', { name: /Violets/i } ) ).getByRole(
			'checkbox'
		);
		const poppyCheckbox = within( screen.getByRole( 'row', { name: /Poppy/i } ) ).getByRole(
			'checkbox'
		);

		// Select two items
		await user.click( violetsCheckbox );
		await user.click( poppyCheckbox );

		// Deselect one item
		await user.click( violetsCheckbox );

		// Assert that only one is checked
		expect( violetsCheckbox ).not.toBeChecked();
		expect( poppyCheckbox ).toBeChecked();

		// Click the Save button
		const saveButton = screen.getByRole( 'button', { name: 'Save' } );
		await user.click( saveButton );

		// Verify onSelect was called with the correct item
		expect( onSelect ).toHaveBeenCalledWith(
			expect.arrayContaining( [
				{
					result: {
						title: {
							name: 'Title',
							type: 'title',
							value: 'Poppy',
						},
					},
					uuid: 'poppy',
				},
			] )
		);
	} );

	it( 'should render pagination buttons when there is more than one page', async () => {
		const onSelect = vi.fn();
		const user = userEvent.setup();
		const setPage = vi.fn();

		const { rerender } = render(
			<ItemListComponent
				{ ...defaultProps }
				onSelect={ onSelect }
				setPage={ setPage }
				totalPages={ 3 }
			/>
		);

		expect(
			screen.getByRole( 'option', {
				name: 'Page 1 of 3',
				selected: true,
			} )
		).toBeVisible();

		// Get next and previous buttons
		const nextButton = screen.getByRole( 'button', { name: /next/i } );
		const previousButton = screen.getByRole( 'button', { name: /previous/i } );

		// Previous button should be disabled
		expect( previousButton ).toHaveAttribute( 'aria-disabled', 'true' );

		// Simulate clicking the next page button
		await user.click( nextButton );

		// Verify setPage was called with next page
		expect( setPage ).toHaveBeenCalledWith( 2 );
		expect(
			screen.getByRole( 'option', {
				name: 'Page 2 of 3',
				selected: true,
			} )
		).toHaveValue( '2' );

		// Rerender the component with updated page
		rerender(
			<ItemListComponent
				{ ...defaultProps }
				onSelect={ onSelect }
				page={ 2 } // Now render page 2
				setPage={ setPage }
				totalPages={ 3 }
			/>
		);

		// Simulate clicking the next page button
		await user.click( nextButton );

		// Verify setPage was called with next page
		expect( setPage ).toHaveBeenCalledWith( 3 );
		expect(
			screen.getByRole( 'option', {
				name: 'Page 3 of 3',
				selected: true,
			} )
		).toBeVisible();

		// Next button should be disabled
		expect( nextButton ).toHaveAttribute( 'aria-disabled', 'true' );

		// Simulate clicking the previous page button
		await user.click( previousButton );

		// Verify setPage was called with previous page
		expect( setPage ).toHaveBeenCalledWith( 2 );
	} );
} );
