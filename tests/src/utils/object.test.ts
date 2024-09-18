import { describe, expect, it } from 'vitest';

import { constructObjectWithValues, isNonEmptyObj } from '@/utils/object';

describe( 'object utils', () => {
	describe( 'isNonEmptyObj', () => {
		it( 'should return true for non-empty objects', () => {
			expect( isNonEmptyObj( { key: 'value' } ) ).toBe( true );
			expect( isNonEmptyObj( { a: 1, b: 2 } ) ).toBe( true );
		} );

		it( 'should return false for empty objects', () => {
			expect( isNonEmptyObj( {} ) ).toBe( false );
		} );

		it( 'should return false for null', () => {
			expect( isNonEmptyObj( null ) ).toBe( false );
		} );

		it( 'should return false for non-object types', () => {
			expect( isNonEmptyObj( 'string' ) ).toBe( false );
			expect( isNonEmptyObj( 123 ) ).toBe( false );
			expect( isNonEmptyObj( true ) ).toBe( false );
			expect( isNonEmptyObj( undefined ) ).toBe( false );
			expect( isNonEmptyObj( [] ) ).toBe( false );
		} );
	} );

	describe( 'constructObjectWithValues', () => {
		it( 'should construct an object with default values', () => {
			const input = { a: 1, b: 'two', c: true };
			const defaultValue = 'default';
			const expected = { a: 'default', b: 'default', c: 'default' };
			expect( constructObjectWithValues( input, defaultValue ) ).toEqual( expected );
		} );

		it( 'should handle empty input object', () => {
			const input = {};
			const defaultValue = 0;
			expect( constructObjectWithValues( input, defaultValue ) ).toEqual( {} );
		} );

		it( 'should work with different default value types', () => {
			const input = { x: 'x', y: 'y' };
			expect( constructObjectWithValues( input, 42 ) ).toEqual( { x: 42, y: 42 } );
			expect( constructObjectWithValues( input, true ) ).toEqual( { x: true, y: true } );
			expect( constructObjectWithValues( input, null ) ).toEqual( { x: null, y: null } );
		} );
	} );
} );
