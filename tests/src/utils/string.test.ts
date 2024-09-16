import { describe, expect, it } from 'vitest';
import {
	combineClassNames,
	getClassName,
	safeParseJSON,
	slugToTitleCase,
	toKebabCase,
	toTitleCase,
} from '../../../src/utils/string';

describe( 'string utils', () => {
	describe( 'getClassName', () => {
		it( 'should generate a class name with prefix', () => {
			expect( getClassName( 'testName' ) ).toBe( 'rdb-testname' );
		} );

		it( 'should combine with existing class name', () => {
			expect( getClassName( 'testName', 'existing-class' ) ).toBe( 'existing-class rdb-testname' );
		} );
	} );

	describe( 'combineClassNames', () => {
		it( 'should combine multiple class names', () => {
			expect( combineClassNames( 'class1', 'class2', 'class3' ) ).toBe( 'class1 class2 class3' );
		} );

		it( 'should filter out falsy values', () => {
			expect( combineClassNames( 'class1', undefined, 'class2', '', 'class3' ) ).toBe(
				'class1 class2 class3'
			);
		} );
	} );

	describe( 'toKebabCase', () => {
		it( 'should convert string to kebab-case', () => {
			expect( toKebabCase( 'Test String' ) ).toBe( 'test-string' );
			expect( toKebabCase( 'TestString' ) ).toBe( 'teststring' );
			expect( toKebabCase( 'test_string' ) ).toBe( 'test-string' );
		} );
	} );

	describe( 'toTitleCase', () => {
		it( 'should convert string to title case', () => {
			expect( toTitleCase( 'test string' ) ).toBe( 'Test String' );
			expect( toTitleCase( 'TEST STRING' ) ).toBe( 'Test String' );
			expect( toTitleCase( 'tEST sTRING' ) ).toBe( 'Test String' );
		} );
	} );

	describe( 'slugToTitleCase', () => {
		it( 'should convert slug to title case', () => {
			expect( slugToTitleCase( 'test-string' ) ).toBe( 'Test String' );
			expect( slugToTitleCase( 'another-test-string' ) ).toBe( 'Another Test String' );
		} );
	} );

	describe( 'safeParseJSON', () => {
		it( 'should parse valid JSON string', () => {
			expect( safeParseJSON( '{"key": "value"}' ) ).toEqual( { key: 'value' } );
		} );

		it( 'should return null for invalid JSON string', () => {
			expect( safeParseJSON( '{invalid json}' ) ).toBeNull();
		} );

		it( 'should return null for empty string', () => {
			expect( safeParseJSON( '' ) ).toBeNull();
		} );

		it( 'should return null for null input', () => {
			expect( safeParseJSON( null ) ).toBeNull();
		} );

		it( 'should return null for undefined input', () => {
			expect( safeParseJSON( undefined ) ).toBeNull();
		} );
	} );
} );
