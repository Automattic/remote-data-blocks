import { describe, expect, it } from 'vitest';

import { isQueryInputValid, validateQueryInput } from '@/utils/input-validation';

describe( 'validateQueryInput', () => {
	it( 'should validate query input', () => {
		const queryInput: RemoteDataQueryInput = {
			input1: 'value1',
			input2: 'value2',
		};
		const inputVariables: InputVariable[] = [
			{ slug: 'input1', required: true, type: 'string' },
			{ slug: 'input2', required: true, type: 'string' },
			{ slug: 'input3', required: false, type: 'string' },
		];

		expect( () => validateQueryInput( queryInput, inputVariables ) ).not.toThrow();
		expect( isQueryInputValid( queryInput, inputVariables ) ).toBe( true );
	} );

	it( 'should throw an error if query input is missing required variables', () => {
		const queryInput: RemoteDataQueryInput = {
			input1: 'value1',
		};
		const inputVariables: InputVariable[] = [
			{ slug: 'input1', required: true, type: 'string' },
			{ slug: 'input2', required: true, type: 'string' },
		];

		expect( () => validateQueryInput( queryInput, inputVariables ) ).toThrowError(
			'Missing required query input variables'
		);
		expect( isQueryInputValid( queryInput, inputVariables ) ).toBe( false );
	} );

	it( 'should not throw an error for non-nullish but falsy values', () => {
		const queryInput: RemoteDataQueryInput = {
			input1: '',
			input2: false,
		};
		const inputVariables: InputVariable[] = [
			{ slug: 'input1', required: true, type: 'string' },
			{ slug: 'input2', required: true, type: 'boolean' },
		];

		expect( validateQueryInput( queryInput, inputVariables ) ).toBe( true );
		expect( isQueryInputValid( queryInput, inputVariables ) ).toBe( true );
	} );
} );
