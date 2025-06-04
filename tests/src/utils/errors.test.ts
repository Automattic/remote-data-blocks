import { describe, expect, it } from 'vitest';

import { ensureError } from '@/utils/errors';

describe( 'ensureError', () => {
	it( 'should return the error if it is an instance of Error', () => {
		const error = new Error( 'Test error' );
		expect( ensureError( error ) ).toBe( error );
	} );

	it( 'should return a new Error if the input is null', () => {
		expect( ensureError( null ) ).toEqual( new Error( 'An unknown error occurred' ) );
	} );

	it( 'should return a new Error if the input is undefined', () => {
		expect( ensureError( undefined ) ).toEqual( new Error( 'An unknown error occurred' ) );
	} );

	it( 'should return a new Error if the input is a string', () => {
		const message = 'This is an error message';
		expect( ensureError( message ) ).toEqual( new Error( message ) );
	} );

	it( 'should return a new Error if the input is an object with a message property', () => {
		const errorObject = { message: 'Object error message' };
		expect( ensureError( errorObject ) ).toEqual( new Error( 'Object error message' ) );
	} );

	it( 'should return a new Error for any other type of input', () => {
		const numberInput = 42;
		expect( ensureError( numberInput ).message ).toEqual( '42' );
	} );
} );
