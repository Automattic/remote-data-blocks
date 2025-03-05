import { describe, expect, it } from 'vitest';

import {
	createRemoteDataResults,
	createQueryInputsFromRemoteDataResults,
	getRemoteDataResultValue,
	getFirstRemoteDataResultValueByType,
} from '@/utils/remote-data';

describe( 'remote-data utils', () => {
	describe( 'createRemoteDataResults', () => {
		it( 'should create remote data results', () => {
			const objs = [ { title: 'Title 1' }, { title: 'Title 2' } ];

			const results = createRemoteDataResults( objs );

			expect( results ).toEqual( [
				{
					result: {
						title: { name: 'title', type: 'string', value: 'Title 1' },
					},
					uuid: 'test-uuid-0',
				},
				{
					result: {
						title: { name: 'title', type: 'string', value: 'Title 2' },
					},
					uuid: 'test-uuid-1',
				},
			] );
		} );
	} );

	describe( 'createQueryInputsFromRemoteDataResults', () => {
		it( 'should create query inputs from remote data results', () => {
			const results = [
				{
					result: {
						title: { name: 'title', type: 'string', value: 'Title 1' },
					},
					uuid: 'test-uuid-0',
				},
				{
					result: {
						title: { name: 'title', type: 'string', value: 'Title 2' },
					},
					uuid: 'test-uuid-1',
				},
			];

			const queryInputs = createQueryInputsFromRemoteDataResults( results );

			expect( queryInputs ).toEqual( [ { title: 'Title 1' }, { title: 'Title 2' } ] );
		} );
	} );

	describe( 'getRemoteDataResultValue', () => {
		it( 'should get remote data result value', () => {
			const result = {
				result: {
					title: { name: 'title', type: 'string', value: 'Title 1' },
				},
				uuid: 'test-uuid-0',
			};

			expect( getRemoteDataResultValue( result, 'title' ) ).toBe( 'Title 1' );
			expect( getRemoteDataResultValue( result, 'non-existent' ) ).toBe( '' );
			expect( getRemoteDataResultValue( result, 'non-existent', 'default value' ) ).toBe(
				'default value'
			);
		} );

		it( 'should coerce non-string values to strings', () => {
			const result = {
				result: {
					number: { name: 'number', type: 'number', value: 42 },
					boolean: { name: 'boolean', type: 'boolean', value: true },
				},
				uuid: 'test-uuid-0',
			};

			expect( getRemoteDataResultValue( result, 'number' ) ).toBe( '42' );
			expect( getRemoteDataResultValue( result, 'boolean' ) ).toBe( 'true' );
		} );
	} );

	describe( 'getFirstRemoteDataResultValueByType', () => {
		it( 'should get the first remote data result value by type', () => {
			const result = {
				result: {
					title: { name: 'title', type: 'string', value: 'Title 1' },
					summary1: { name: 'summary', type: 'string', value: 'Summary 1' },
					summary2: { name: 'ID', type: 'id', value: 123 },
				},
				uuid: 'test-uuid-0',
			};

			expect( getFirstRemoteDataResultValueByType( result, 'string' ) ).toBe( 'Title 1' );
			expect( getFirstRemoteDataResultValueByType( result, 'id' ) ).toBe( '123' );
			expect( getFirstRemoteDataResultValueByType( result, 'number' ) ).toBe( '' );
			expect( getFirstRemoteDataResultValueByType( result, 'number', 'default value' ) ).toBe(
				'default value'
			);
		} );
	} );
} );
