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

		it( 'should cache repeated calls of async function', async () => {
			const fn = vi.fn().mockImplementation( async ( arg: number ): Promise< string > => {
				await Promise.resolve();
				return `called with: ${ arg }`;
			} );
			const memoized = memoizeFn( fn );

			expect( await memoized( 1 ) ).toEqual( 'called with: 1' );
			expect( await memoized( 1 ) ).toEqual( 'called with: 1' );
			expect( await memoized( 2 ) ).toEqual( 'called with: 2' );
			expect( await memoized( 2 ) ).toEqual( 'called with: 2' );
			expect( await memoized( 2 ) ).toEqual( 'called with: 2' );
			expect( await memoized( 3 ) ).toEqual( 'called with: 3' );
			expect( await memoized( 3 ) ).toEqual( 'called with: 3' );

			expect( fn ).toHaveBeenCalledTimes( 3 );
		} );

		it( 'should cache a then-able promise if that is returned by the function', async () => {
			const fn = vi.fn().mockImplementation( async ( arg: number ): Promise< string > => {
				await Promise.resolve();
				return `called with: ${ arg }`;
			} );
			const memoized = memoizeFn< ( arg: number ) => Promise< string > >( fn );

			expect( await memoized( 1 ) ).toEqual( 'called with: 1' );
			expect( await memoized( 1 ) ).toEqual( 'called with: 1' );

			const thenPromise = new Promise< string >( ( resolve, reject ) => {
				memoized( 1 ).then( resolve ).catch( reject );
			} );

			expect( await thenPromise ).toEqual( 'called with: 1' );

			expect( fn ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'should not cache promise rejections', async () => {
			const fn = vi
				.fn()
				.mockImplementation(
					async ( arg: number ): Promise< string > =>
						Promise.reject( new Error( `rejected with: ${ arg }` ) )
				);
			const memoized = memoizeFn( fn );

			await expect( memoized( 1 ) ).rejects.toThrowError( 'rejected with: 1' );
			await expect( memoized( 1 ) ).rejects.toThrowError( 'rejected with: 1' );
			await expect( memoized( 2 ) ).rejects.toThrowError( 'rejected with: 2' );
			await expect( memoized( 2 ) ).rejects.toThrowError( 'rejected with: 2' );
			await expect( memoized( 2 ) ).rejects.toThrowError( 'rejected with: 2' );
			await expect( memoized( 3 ) ).rejects.toThrowError( 'rejected with: 3' );
			await expect( memoized( 3 ) ).rejects.toThrowError( 'rejected with: 3' );

			expect( fn ).toHaveBeenCalledTimes( 7 );
		} );

		it( 'should not cache promise rejections via thrown errors', async () => {
			const fn = vi.fn().mockImplementation( async ( arg: number ): Promise< string > => {
				await Promise.resolve();
				throw new Error( `rejected with: ${ arg }` );
			} );
			const memoized = memoizeFn( fn );

			await expect( memoized( 1 ) ).rejects.toThrowError( 'rejected with: 1' );
			await expect( memoized( 1 ) ).rejects.toThrowError( 'rejected with: 1' );
			await expect( memoized( 2 ) ).rejects.toThrowError( 'rejected with: 2' );
			await expect( memoized( 2 ) ).rejects.toThrowError( 'rejected with: 2' );
			await expect( memoized( 2 ) ).rejects.toThrowError( 'rejected with: 2' );
			await expect( memoized( 3 ) ).rejects.toThrowError( 'rejected with: 3' );
			await expect( memoized( 3 ) ).rejects.toThrowError( 'rejected with: 3' );

			expect( fn ).toHaveBeenCalledTimes( 7 );
		} );

		it( 'should not cache thrown errors', () => {
			const fn = vi.fn< ( arg: number ) => never >().mockImplementation( ( arg: number ): never => {
				throw new Error( `thrown with: ${ arg }` );
			} );
			const memoized = memoizeFn( fn );

			expect( () => memoized( 1 ) ).toThrowError( 'thrown with: 1' );
			expect( () => memoized( 1 ) ).toThrowError( 'thrown with: 1' );
			expect( () => memoized( 2 ) ).toThrowError( 'thrown with: 2' );
			expect( () => memoized( 2 ) ).toThrowError( 'thrown with: 2' );
			expect( () => memoized( 2 ) ).toThrowError( 'thrown with: 2' );
			expect( () => memoized( 3 ) ).toThrowError( 'thrown with: 3' );
			expect( () => memoized( 3 ) ).toThrowError( 'thrown with: 3' );

			expect( fn ).toHaveBeenCalledTimes( 7 );
		} );
	} );
} );
