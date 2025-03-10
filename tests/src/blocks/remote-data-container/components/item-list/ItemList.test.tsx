import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { describe, expect, it, vi } from 'vitest';

import {
	ItemList,
	ItemListProps,
} from '@/blocks/remote-data-container/components/item-list/ItemList';

const mockRemoteData: RemoteData = {
	blockName: 'Test Block',
	isCollection: true,
	metadata: {},
	queryInput: {},
	resultId: 'test-result',
	results: [
		{ id: 'violets', title: 'Violets' },
		{ id: 'crimson-clover', title: 'Crimson Clover' },
		{ id: 'poppy', title: 'Poppy' },
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
	remoteData: mockRemoteData,
	searchInput: '',
	selectedItems: [],
	setPage: () => {},
	setPerPage: () => {},
	setSearchInput: () => {},
	setSelectedItems: () => {},
	supportsBulk: false,
	supportsSearch: false,
};

const ItemListComponent = ( props: ItemListProps ) => {
	const [ selectedItems, setSelectedItems ] = useState< string[] >( [] );
	return (
		<>
			<ItemList
				{ ...props }
				selectedItems={ selectedItems }
				setSelectedItems={ setSelectedItems }
				onSelect={ props.onSelect }
			/>
			<Button onClick={ () => setSelectedItems( [] ) }>Cancel</Button>
			<Button onClick={ () => props.onSelect( { id: selectedItems.join( ',' ) } ) }>Save</Button>
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
			expect.objectContaining( {
				id: 'poppy',
				title: 'Poppy',
			} )
		);
	} );

	it( 'should allow bulk selection of items', async () => {
		const onSelect = vi.fn();
		const user = userEvent.setup();

		render( <ItemListComponent { ...defaultProps } onSelect={ onSelect } supportsBulk={ true } /> );

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
			expect.objectContaining( {
				id: 'violets,poppy',
			} )
		);
	} );

	it( 'should allow deselection of items', async () => {
		const onSelect = vi.fn();
		const user = userEvent.setup();

		render( <ItemListComponent { ...defaultProps } supportsBulk={ true } onSelect={ onSelect } /> );

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
			expect.objectContaining( {
				id: 'poppy',
			} )
		);
	} );
} );
