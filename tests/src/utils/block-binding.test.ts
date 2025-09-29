import { describe, expect, it } from 'vitest';

import { BLOCK_BINDING_SOURCE } from '@/config/constants';
import { getBoundAttributeEntries } from '@/utils/block-binding';

describe( 'block-binding utils', () => {
	describe( 'getBoundAttributeEntries', () => {
		it( 'should return bound attribute entries', () => {
			const block = 'test/block';
			const attributes: RemoteDataInnerBlockAttributes = {
				metadata: {
					bindings: {
						content: { source: BLOCK_BINDING_SOURCE, args: { block, field: 'title' } },
						text: { source: BLOCK_BINDING_SOURCE, args: { block: 'test/block2', field: 'text' } },
						url: { source: BLOCK_BINDING_SOURCE, args: { block, field: 'link' } },
						alt: { source: 'other', args: { block, field: 'description' } },
					},
				},
			};

			const result = getBoundAttributeEntries( attributes, block );

			expect( result ).toEqual( [
				[ 'content', { source: BLOCK_BINDING_SOURCE, args: { block, field: 'title' } } ],
				[ 'url', { source: BLOCK_BINDING_SOURCE, args: { block, field: 'link' } } ],
			] );
		} );

		it( 'should return an empty array when no bindings are present', () => {
			const attributes: RemoteDataInnerBlockAttributes = {};

			const result = getBoundAttributeEntries( attributes, 'test/block' );

			expect( result ).toEqual( [] );
		} );
	} );
} );
