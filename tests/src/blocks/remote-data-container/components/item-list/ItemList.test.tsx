import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
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
	onSelect: () => {},
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
		<ItemList { ...props } selectedItems={ selectedItems } setSelectedItems={ setSelectedItems } />
	);
};

describe( 'ItemList', () => {
	it( 'should render rows when there are results', () => {
		render( <ItemListComponent { ...defaultProps } /> );

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

		const poppyRow = screen.getByRole( 'row', { name: /Poppy/i } );

		await user.click( within( poppyRow ).getByRole( 'button', { name: 'Choose' } ) );

		expect( onSelect ).toHaveBeenCalledWith(
			expect.objectContaining( {
				id: 'poppy',
				title: 'Poppy',
			} )
		);
	} );

	it( 'should allow bulk selection of items', async () => {
		const user = userEvent.setup();

		render( <ItemListComponent { ...defaultProps } supportsBulk={ true } /> );

		const violetsRow = screen.getByRole( 'row', { name: /Violets/i } );
		const violetsCheckbox = within( violetsRow ).getByRole( 'checkbox' );

		const poppyRow = screen.getByRole( 'row', { name: /Poppy/i } );
		const poppyCheckbox = within( poppyRow ).getByRole( 'checkbox' );

		const crimsonCloverRow = screen.getByRole( 'row', { name: /Crimson Clover/i } );
		const crimsonCloverCheckbox = within( crimsonCloverRow ).getByRole( 'checkbox' );

		// Select two items
		await user.click( violetsCheckbox );
		await user.click( poppyCheckbox );

		// Assert that both are checked
		expect( violetsCheckbox ).toBeChecked();
		expect( poppyCheckbox ).toBeChecked();

		expect( crimsonCloverCheckbox ).not.toBeChecked();
	} );

	it( 'should allow deselection', async () => {
		const user = userEvent.setup();

		render( <ItemListComponent { ...defaultProps } supportsBulk={ true } /> );

		const violetsRow = screen.getByRole( 'row', { name: /Violets/i } );
		const violetsCheckbox = within( violetsRow ).getByRole( 'checkbox' );

		const poppyRow = screen.getByRole( 'row', { name: /Poppy/i } );
		const poppyCheckbox = within( poppyRow ).getByRole( 'checkbox' );

		// Select two items
		await user.click( violetsCheckbox );
		await user.click( poppyCheckbox );

		// Deselect one item
		await user.click( violetsCheckbox );

		// Assert that only one is checked
		expect( violetsCheckbox ).not.toBeChecked();
		expect( poppyCheckbox ).toBeChecked();
	} );
} );
