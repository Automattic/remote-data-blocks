import { describe, expect, it } from 'vitest';

import { coerceBindingValue } from '@/utils/block-binding-value';

describe( 'coerceBindingValue', () => {
	it.each( [
		[ 0, 0 ],
		[ ' -1.25e2 ', -125 ],
		[ '.5', 0.5 ],
	] )( 'converts numeric %s to %s', ( input, expected ) => {
		expect( coerceBindingValue( input, 'number' ) ).toBe( expected );
	} );
	it.each( [ null, undefined, false, '', '  ', '1px', '0x10', 'Infinity', Infinity, NaN, {}, [] ] )(
		'rejects invalid numeric %s',
		input => {
			expect( coerceBindingValue( input, 'number' ) ).toBeNull();
		}
	);
	it( 'requires safe integers without silently truncating', () => {
		expect( coerceBindingValue( '42', 'integer' ) ).toBe( 42 );
		expect( coerceBindingValue( 1.5, 'integer' ) ).toBeNull();
		expect( coerceBindingValue( 9007199254740992, 'integer' ) ).toBeNull();
	} );
	it( 'preserves actual booleans, including false, without truthiness coercion', () => {
		expect( coerceBindingValue( false, 'boolean' ) ).toBe( false );
		expect( coerceBindingValue( true, 'boolean' ) ).toBe( true );
		for ( const value of [ 'false', 'true', '0', '1', 0, 1, null ] ) {
			expect( coerceBindingValue( value, 'boolean' ) ).toBeNull();
		}
	} );
	it.each( [ 'object', 'array', 'null', 'unsupported' ] )(
		'rejects unsupported target %s rather than serializing it',
		target => {
			expect( coerceBindingValue( { nested: 'value' }, target ) ).toBeNull();
		}
	);
	it( 'retains formatted text and unknown-target string behavior', () => {
		expect( coerceBindingValue( '$1,234.00', 'string' ) ).toBe( '$1,234.00' );
		expect( coerceBindingValue( 0, 'rich-text' ) ).toBe( '0' );
		expect( coerceBindingValue( false ) ).toBe( 'false' );
		expect( coerceBindingValue( '', 'string' ) ).toBe( '' );
	} );
	it.each( [ 'string', 'rich-text', undefined ] )(
		'treats absent values as unresolved for text target %s',
		target => {
			expect( coerceBindingValue( null, target ) ).toBeNull();
			expect( coerceBindingValue( undefined, target ) ).toBeNull();
		}
	);
	it.each( [ 'string', 'rich-text', undefined ] )(
		'treats structured values as unresolved for text target %s',
		target => {
			expect( coerceBindingValue( { nested: 'value' }, target ) ).toBeNull();
			expect( coerceBindingValue( [ 'a', 'b' ], target ) ).toBeNull();
			expect( coerceBindingValue( Object.create( null ), target ) ).toBeNull();
		}
	);
	it( 'keeps class instances that own their string form, such as saved rich text', () => {
		class RichTextLike {
			toString() {
				return 'Saved <em>rich</em> text';
			}
		}
		expect( coerceBindingValue( new RichTextLike(), 'rich-text' ) ).toBe(
			'Saved <em>rich</em> text'
		);
	} );
} );
