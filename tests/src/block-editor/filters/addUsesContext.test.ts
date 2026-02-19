import { describe, expect, it, vi } from 'vitest';
import { BlockConfiguration } from '@wordpress/blocks';

import { addUsesContext } from '@/block-editor/filters/addUsesContext';
import { REMOTE_DATA_CONTEXT_KEY, SUPPORTED_CORE_BLOCKS } from '@/blocks/remote-data-container/config/constants';

// Mock the applyFilters function
vi.mock( '@wordpress/hooks', () => ( {
	applyFilters: vi.fn( ( _filterName, supportedBlocks ) => supportedBlocks ),
} ) );

describe( 'addUsesContext', () => {
	it( 'should add context to supported core blocks', () => {
		const settings: BlockConfiguration< RemoteDataInnerBlockAttributes > = {
			attributes: {},
			category: 'text',
			title: 'Test Block',
		};

		const result = addUsesContext( settings, 'core/paragraph' );

		expect( result.usesContext ).toContain( REMOTE_DATA_CONTEXT_KEY );
	} );

	it( 'should not modify blocks not in the supported list', () => {
		const settings: BlockConfiguration< RemoteDataInnerBlockAttributes > = {
			attributes: {},
			category: 'text',
			title: 'Test Block',
		};

		const result = addUsesContext( settings, 'core/unknown-block' );

		expect( result ).toEqual( settings );
		expect( result.usesContext ).toBeUndefined();
	} );

	it( 'should preserve existing usesContext values', () => {
		const settings: BlockConfiguration< RemoteDataInnerBlockAttributes > = {
			attributes: {},
			category: 'text',
			title: 'Test Block',
			usesContext: [ 'existing/context' ],
		};

		const result = addUsesContext( settings, 'core/heading' );

		expect( result.usesContext ).toContain( 'existing/context' );
		expect( result.usesContext ).toContain( REMOTE_DATA_CONTEXT_KEY );
	} );

	it( 'should not duplicate context if already present', () => {
		const settings: BlockConfiguration< RemoteDataInnerBlockAttributes > = {
			attributes: {},
			category: 'text',
			title: 'Test Block',
			usesContext: [ REMOTE_DATA_CONTEXT_KEY ],
		};

		const result = addUsesContext( settings, 'core/button' );

		expect( result ).toEqual( settings );
		const contextCount = result.usesContext?.filter( ctx => ctx === REMOTE_DATA_CONTEXT_KEY ).length;
		expect( contextCount ).toBe( 1 );
	} );

	it( 'should add context to all supported blocks', () => {
		SUPPORTED_CORE_BLOCKS.forEach( blockName => {
			const settings: BlockConfiguration< RemoteDataInnerBlockAttributes > = {
				attributes: {},
				category: 'text',
				title: 'Test Block',
			};

			const result = addUsesContext( settings, blockName );

			expect( result.usesContext ).toContain( REMOTE_DATA_CONTEXT_KEY );
		} );
	} );

	it( 'should support blocks with __experimentalLabel', () => {
		const settings: BlockConfiguration< RemoteDataInnerBlockAttributes > = {
			attributes: {
				content: {
					type: 'string',
					// @ts-ignore - __experimentalLabel is not in types
					__experimentalLabel: 'Content',
				},
			},
			category: 'text',
			title: 'Test Block',
		};

		const result = addUsesContext( settings, 'custom/block-with-label' );

		expect( result.usesContext ).toContain( REMOTE_DATA_CONTEXT_KEY );
	} );

	it( 'should support blocks with supports.bindings', () => {
		const settings: BlockConfiguration< RemoteDataInnerBlockAttributes > = {
			attributes: {},
			category: 'text',
			title: 'Test Block',
			// @ts-ignore - bindings is not in types
			supports: {
				bindings: true,
			},
		};

		const result = addUsesContext( settings, 'custom/block-with-bindings' );

		expect( result.usesContext ).toContain( REMOTE_DATA_CONTEXT_KEY );
	} );
} );
