import { describe, expect, it } from 'vitest';

import { BLOCK_BINDING_SOURCE } from '../../../src/config/constants';
import {
	getBoundAttributeEntries,
	getMismatchedAttributes,
} from '../../../src/utils/block-binding';

describe( 'block-binding utils', () => {
	describe( 'getBoundAttributeEntries', () => {
		it( 'should return bound attribute entries', () => {
			const attributes: ContextInnerBlockAttributes = {
				metadata: {
					bindings: {
						content: { source: BLOCK_BINDING_SOURCE, args: { field: 'title' } },
						url: { source: BLOCK_BINDING_SOURCE, args: { field: 'link' } },
						alt: { source: 'other', args: { field: 'description' } },
					},
				},
			};

			const result = getBoundAttributeEntries( attributes );

			expect( result ).toEqual( [
				[ 'content', { source: BLOCK_BINDING_SOURCE, args: { field: 'title' } } ],
				[ 'url', { source: BLOCK_BINDING_SOURCE, args: { field: 'link' } } ],
			] );
		} );

		it( 'should return an empty array when no bindings are present', () => {
			const attributes: ContextInnerBlockAttributes = {};

			const result = getBoundAttributeEntries( attributes );

			expect( result ).toEqual( [] );
		} );
	} );

	describe( 'getMismatchedAttributes', () => {
		it( 'should return mismatched attributes', () => {
			const attributes: ContextInnerBlockAttributes = {
				content: 'Old content',
				url: 'https://old-url.com',
				alt: 'Old alt',
				metadata: {
					bindings: {
						content: { source: BLOCK_BINDING_SOURCE, args: { field: 'title' } },
						url: { source: BLOCK_BINDING_SOURCE, args: { field: 'link' } },
					},
				},
			};

			const results = [ { title: 'New content', link: 'https://new-url.com' } ];

			const result = getMismatchedAttributes( attributes, results );

			expect( result ).toEqual( {
				content: 'New content',
				url: 'https://new-url.com',
			} );
		} );

		it( 'should return an empty object when no mismatches are found', () => {
			const attributes: ContextInnerBlockAttributes = {
				content: 'Current content',
				url: 'https://current-url.com',
				metadata: {
					bindings: {
						content: { source: BLOCK_BINDING_SOURCE, args: { field: 'title' } },
						url: { source: BLOCK_BINDING_SOURCE, args: { field: 'link' } },
					},
				},
			};

			const results = [ { title: 'Current content', link: 'https://current-url.com' } ];

			const result = getMismatchedAttributes( attributes, results );

			expect( result ).toEqual( {} );
		} );

		it( 'should handle missing results', () => {
			const attributes: ContextInnerBlockAttributes = {
				content: 'Old content',
				url: 'https://old-url.com',
				metadata: {
					bindings: {
						content: { source: BLOCK_BINDING_SOURCE, args: { field: 'title' } },
						url: { source: BLOCK_BINDING_SOURCE, args: { field: 'link' } },
					},
				},
			};

			const results: Record< string, string >[] = [ { title: 'New content' } ];

			const result = getMismatchedAttributes( attributes, results );

			expect( result ).toEqual( {
				content: 'New content',
			} );
		} );
	} );
} );
