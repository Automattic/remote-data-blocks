import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';

import { QueryInputsPanel } from '@/blocks/remote-data-container/components/panels/QueryInputsPanel';

describe( 'QueryInputsPanel', () => {
	const selectors: BlockConfig[ 'selectors' ] = [
		{
			inputs: [
				{
					name: 'id',
					required: true,
					slug: 'id',
					type: 'string',
					default_value: '',
				},
				{
					name: 'record_id',
					required: false,
					slug: 'record_id',
					type: 'string',
					default_value: '',
				},
			],
			name: 'test_selector',
			query_key: 'test_query_key',
			type: 'manual',
		},
	];

	const remoteData: RemoteData = {
		blockName: 'test/block',
		metadata: {},
		queryKey: 'test_query_key',
		queryInputs: [],
		resultId: 'test',
		results: [],
	};

	it( 'should render multiple inputs for query inputs with an array of ids', async () => {
		const user = userEvent.setup();
		const onUpdateQueryInputs = vi.fn();
		render(
			<QueryInputsPanel
				onUpdateQueryInputs={ onUpdateQueryInputs }
				remoteData={ {
					...remoteData,
					queryInputs: [ { id: 'test1' }, { id: 'test2' }, { id: 'test3' } ],
				} }
				selectors={ selectors }
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
		expect( onUpdateQueryInputs ).toHaveBeenCalledWith( 'test_query_key', [
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
				onUpdateQueryInputs={ onUpdateQueryInputs }
				remoteData={ {
					...remoteData,
					queryInputs: [
						{
							record_id: [ 'test1', 'test2', 'test3', 'test4' ],
						},
					],
				} }
				selectors={ selectors }
			/>
		);

		await user.click( screen.getByRole( 'textbox' ) );
		// Clear the input field
		await user.clear( screen.getByRole( 'textbox' ) );
		// Type a new value
		await user.type( screen.getByRole( 'textbox' ), 'test5' );
		await user.tab();

		expect( onUpdateQueryInputs ).toHaveBeenCalledWith( 'test_query_key', [
			{
				record_id: 'test5',
			},
		] );

		// Add additional value
		await user.type( screen.getByRole( 'textbox' ), ',test6' );
		await user.tab();

		expect( onUpdateQueryInputs ).toHaveBeenCalledWith( 'test_query_key', [
			{
				record_id: 'test5,test6',
			},
		] );
	} );
} );
