import { describe, expect, it } from 'vitest';

import { isObjectWithStringKeys } from '@/utils/type-narrowing';

describe( 'type-narrowing utils', () => {
	describe( 'isObjectWithStringKeys', () => {
		it( 'should return true for objects with string keys', () => {
			expect( isObjectWithStringKeys( { a: 1, b: 'two' } ) ).toBe( true );
			expect( isObjectWithStringKeys( { 'key-with-dash': true } ) ).toBe( true );
			expect( isObjectWithStringKeys( {} ) ).toBe( true );
		} );

		it( 'should return false for arrays', () => {
			expect( isObjectWithStringKeys( [] ) ).toBe( false );
			expect( isObjectWithStringKeys( [ 1, 2, 3 ] ) ).toBe( false );
		} );

		it( 'should return false for null', () => {
			expect( isObjectWithStringKeys( null ) ).toBe( false );
		} );

		it( 'should return false for primitive types', () => {
			expect( isObjectWithStringKeys( 'string' ) ).toBe( false );
			expect( isObjectWithStringKeys( 123 ) ).toBe( false );
			expect( isObjectWithStringKeys( true ) ).toBe( false );
			expect( isObjectWithStringKeys( undefined ) ).toBe( false );
		} );

		it( 'should return false for functions', () => {
			expect( isObjectWithStringKeys( () => {} ) ).toBe( false );
		} );

		it( 'should return true for objects with symbol keys', () => {
			const obj = { [ Symbol( 'test' ) ]: 'value' };
			expect( isObjectWithStringKeys( obj ) ).toBe( true );
		} );
	} );
} );
