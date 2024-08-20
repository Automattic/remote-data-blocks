import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';

import { InputPanel } from '../../../../../src/blocks/context-container/components/input-panel';

describe( 'InputPanel', () => {
	const mockOnSelect = vi.fn();
	const mockPanel = {
		inputs: [
			{ slug: 'input1', name: 'Input 1', required: true, type: 'text' },
			{ slug: 'input2', name: 'Input 2', required: false, type: 'text' },
		] as InputVariable[],
		name: 'Test Panel',
		query_key: 'test_query',
	};

	const defaultProps = {
		blockName: 'test-block',
		onSelect: mockOnSelect,
		panel: mockPanel,
	};

	afterEach( cleanup );

	it( 'renders the "Provide manual input" button', () => {
		render( <InputPanel { ...defaultProps } /> );
		expect( screen.getByText( 'Provide manual input' ) ).toBeInTheDocument();
	} );

	it( 'opens the modal when the button is clicked', () => {
		render( <InputPanel { ...defaultProps } /> );
		fireEvent.click( screen.getByText( 'Provide manual input' ) );
		expect( screen.getByText( 'Test Panel' ) ).toBeInTheDocument();
	} );

	it( 'renders input fields in the modal', () => {
		render( <InputPanel { ...defaultProps } /> );
		fireEvent.click( screen.getByText( 'Provide manual input' ) );
		expect( screen.getByLabelText( 'Input 1' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( 'Input 2' ) ).toBeInTheDocument();
	} );

	it( 'updates input state when values are entered', () => {
		render( <InputPanel { ...defaultProps } /> );
		fireEvent.click( screen.getByText( 'Provide manual input' ) );

		const input1 = screen.getByLabelText( 'Input 1' );
		fireEvent.change( input1, { target: { value: 'Test Value 1' } } );
		expect( input1 ).toHaveValue( 'Test Value 1' );

		const input2 = screen.getByLabelText( 'Input 2' );
		fireEvent.change( input2, { target: { value: 'Test Value 2' } } );
		expect( input2 ).toHaveValue( 'Test Value 2' );
	} );

	it( 'calls onSelect with input values when Save is clicked', () => {
		render( <InputPanel { ...defaultProps } /> );
		fireEvent.click( screen.getByText( 'Provide manual input' ) );

		fireEvent.change( screen.getByLabelText( 'Input 1' ), { target: { value: 'Test Value 1' } } );
		fireEvent.change( screen.getByLabelText( 'Input 2' ), { target: { value: 'Test Value 2' } } );

		fireEvent.click( screen.getByText( 'Save' ) );

		expect( mockOnSelect ).toHaveBeenCalledWith( {
			input1: 'Test Value 1',
			input2: 'Test Value 2',
		} );
	} );
} );
