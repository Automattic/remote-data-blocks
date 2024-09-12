import { describe, expect, it } from 'vitest';

import { BLOCK_BINDING_SOURCE } from '@/config/constants';
import { getBoundAttributeEntries, getMismatchedAttributes } from '@/utils/block-binding';

describe( 'block-binding utils', () => {
	describe( 'getBoundAttributeEntries', () => {
		it( 'should return bound attribute entries', () => {
			const name = 'test/block';
			const attributes: RemoteDataInnerBlockAttributes = {
				metadata: {
					bindings: {
						content: { source: BLOCK_BINDING_SOURCE, args: { name, field: 'title' } },
						url: { source: BLOCK_BINDING_SOURCE, args: { name, field: 'link' } },
						alt: { source: 'other', args: { name, field: 'description' } },
					},
				},
			};

			const result = getBoundAttributeEntries( attributes );

			expect( result ).toEqual( [
				[ 'content', { source: BLOCK_BINDING_SOURCE, args: { name, field: 'title' } } ],
				[ 'url', { source: BLOCK_BINDING_SOURCE, args: { name, field: 'link' } } ],
			] );
		} );

		it( 'should return an empty array when no bindings are present', () => {
			const attributes: RemoteDataInnerBlockAttributes = {};

			const result = getBoundAttributeEntries( attributes );

			expect( result ).toEqual( [] );
		} );
	} );

	describe( 'getMismatchedAttributes', () => {
		it( 'should return mismatched attributes', () => {
			const name = 'test/block';
			const attributes: RemoteDataInnerBlockAttributes = {
				content: 'Old content',
				url: 'https://old-url.com',
				alt: 'Old alt',
				metadata: {
					bindings: {
						content: { source: BLOCK_BINDING_SOURCE, args: { name, field: 'title' } },
						url: { source: BLOCK_BINDING_SOURCE, args: { name, field: 'link' } },
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
			const name = 'test/block';
			const attributes: RemoteDataInnerBlockAttributes = {
				content: '<span class="rdb-block-label">Title</span> Current content',
				url: 'https://current-url.com',
				metadata: {
					bindings: {
						content: {
							source: BLOCK_BINDING_SOURCE,
							args: { name, field: 'title', label: 'Title' },
						},
						url: { source: BLOCK_BINDING_SOURCE, args: { name, field: 'link' } },
					},
				},
			};

			const results = [ { title: 'Current content', link: 'https://current-url.com' } ];

			const result = getMismatchedAttributes( attributes, results );

			expect( result ).toEqual( {} );
		} );

		it( 'should handle missing results', () => {
			const name = 'test/block';
			const attributes: RemoteDataInnerBlockAttributes = {
				content: 'Old content',
				url: 'https://old-url.com',
				metadata: {
					bindings: {
						content: { source: BLOCK_BINDING_SOURCE, args: { name, field: 'title' } },
						url: { source: BLOCK_BINDING_SOURCE, args: { name, field: 'link' } },
					},
				},
			};

			const results: Record< string, string >[] = [ { title: 'New content' } ];

			const result = getMismatchedAttributes( attributes, results );

			expect( result ).toEqual( {
				content: 'New content',
			} );
		} );

		it( 'should handle missing label', () => {
			const name = 'test/block';
			const attributes: RemoteDataInnerBlockAttributes = {
				content: 'My Title',
				metadata: {
					bindings: {
						content: {
							source: BLOCK_BINDING_SOURCE,
							args: { name, field: 'title', label: 'Title' },
						},
					},
				},
			};

			const results: Record< string, string >[] = [ { title: 'My Title' } ];

			const result = getMismatchedAttributes( attributes, results );

			expect( result ).toEqual( {
				content: '<span class="rdb-block-label">Title</span> My Title',
			} );
		} );
	} );
} );
