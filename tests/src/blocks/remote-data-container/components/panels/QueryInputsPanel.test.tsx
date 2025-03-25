import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';

import { QueryInputsPanel } from '@/blocks/remote-data-container/components/panels/QueryInputsPanel';

describe( 'QueryInputsPanel', () => {
	it( 'should render multiple inputs for query inputs with an array of ids', async () => {
		const user = userEvent.setup();
		const onUpdateQueryInputs = vi.fn();
		render(
			<QueryInputsPanel
				queryInputs={ [
					{
						id: 'test1',
					},
					{
						id: 'test2',
					},
					{
						id: 'test3',
					},
				] }
				onUpdateQueryInputs={ onUpdateQueryInputs }
			/>
		);

		expect( screen.getAllByRole( 'textbox' ) ).toHaveLength( 3 );
		expect( screen.getByDisplayValue( 'test1' ) ).toBeVisible();
		expect( screen.getByDisplayValue( 'test2' ) ).toBeVisible();
		expect( screen.getByDisplayValue( 'test3' ) ).toBeVisible();

		const firstInputField = screen.getAllByRole( 'textbox', {
			name: 'id',
		} )[ 0 ] as HTMLInputElement;

		// Clear the input field
		await user.clear( firstInputField );
		expect( firstInputField ).toHaveValue( '' );
		// Type a new value
		await user.type( firstInputField, 'test4' );
		await user.tab();

		// Called with only the first input value changed
		expect( onUpdateQueryInputs ).toHaveBeenCalledWith( [
			{
				id: 'test4',
			},
			{
				id: 'test2',
			},
			{
				id: 'test3',
			},
		] );
	} );

	it( 'should render a single input for comma separated values', async () => {
		const user = userEvent.setup();
		const onUpdateQueryInputs = vi.fn();
		render(
			<QueryInputsPanel
				queryInputs={ [
					{
						record_id: [ 'test1', 'test2', 'test3', 'test4' ],
					},
				] }
				onUpdateQueryInputs={ onUpdateQueryInputs }
			/>
		);

		await user.click( screen.getByRole( 'textbox' ) );
		// Clear the input field
		await user.clear( screen.getByRole( 'textbox' ) );
		// Type a new value
		await user.type( screen.getByRole( 'textbox' ), 'test5' );
		await user.tab();

		expect( onUpdateQueryInputs ).toHaveBeenCalledWith( [
			{
				record_id: 'test5',
			},
		] );

		// Add additional value
		await user.type( screen.getByRole( 'textbox' ), ',test6' );
		await user.tab();

		expect( onUpdateQueryInputs ).toHaveBeenCalledWith( [
			{
				record_id: 'test5,test6',
			},
		] );
	} );
} );
