import { describe, expect, it, vi } from 'vitest';

import { memoizeFn } from '@/utils/function';

describe( 'function utils', () => {
	describe( 'memoizeFn', () => {
		it( 'should cache repeated calls', () => {
			const fn = vi.fn().mockImplementation( ( arg: number ): string => `called with: ${ arg }` );
			const memoized = memoizeFn( fn );

			expect( memoized( 1 ) ).toEqual( 'called with: 1' );
			expect( memoized( 1 ) ).toEqual( 'called with: 1' );
			expect( memoized( 2 ) ).toEqual( 'called with: 2' );
			expect( memoized( 2 ) ).toEqual( 'called with: 2' );
			expect( memoized( 2 ) ).toEqual( 'called with: 2' );
			expect( memoized( 3 ) ).toEqual( 'called with: 3' );
			expect( memoized( 3 ) ).toEqual( 'called with: 3' );

			expect( fn ).toHaveBeenCalledTimes( 3 );
		} );
	} );
} );
