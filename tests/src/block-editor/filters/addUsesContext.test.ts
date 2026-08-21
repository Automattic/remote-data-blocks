import { describe, expect, it, vi, beforeEach } from 'vitest';
import { BlockConfiguration } from '@wordpress/blocks';

import { addUsesContext } from '@/block-editor/filters/addUsesContext';
import { REMOTE_DATA_CONTEXT_KEY } from '@/blocks/remote-data-container/config/constants';

// Mock the WordPress data store
const mockGetSettings = vi.fn();
const mockSelect = vi.fn( () => ( {
	getSettings: mockGetSettings,
} ) );

vi.mock( '@wordpress/data', () => ( {
	select: mockSelect,
} ) );

vi.mock( '@wordpress/block-editor', () => ( {
	store: 'core/block-editor',
} ) );

describe( 'addUsesContext', () => {
	beforeEach( () => {
		// Reset mocks before each test
		mockGetSettings.mockClear();
		mockSelect.mockClear();
	} );

	it( 'should add context to blocks that support bindings according to WordPress', () => {
		// Mock the block editor settings with supported bindings
		mockGetSettings.mockReturnValue( {
			__experimentalBlockBindingsSupportedAttributes: {
				'core/paragraph': [ 'content' ],
				'core/heading': [ 'content' ],
				'core/image': [ 'url', 'alt', 'title' ],
			},
		} );

		const settings: BlockConfiguration< RemoteDataInnerBlockAttributes > = {
			attributes: {},
			category: 'text',
			title: 'Test Block',
		};

		const result = addUsesContext( settings, 'core/paragraph' );

		expect( result.usesContext ).toContain( REMOTE_DATA_CONTEXT_KEY );
	} );

	it( 'should not modify blocks that do not support bindings', () => {
		// Mock the block editor settings without the block
		mockGetSettings.mockReturnValue( {
			__experimentalBlockBindingsSupportedAttributes: {
				'core/paragraph': [ 'content' ],
			},
		} );

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
		mockGetSettings.mockReturnValue( {
			__experimentalBlockBindingsSupportedAttributes: {
				'core/heading': [ 'content' ],
			},
		} );

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
		mockGetSettings.mockReturnValue( {
			__experimentalBlockBindingsSupportedAttributes: {
				'core/button': [ 'url', 'text', 'linkTarget', 'rel' ],
			},
		} );

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

	it( 'should handle blocks with multiple bindable attributes', () => {
		mockGetSettings.mockReturnValue( {
			__experimentalBlockBindingsSupportedAttributes: {
				'core/image': [ 'url', 'alt', 'title' ],
			},
		} );

		const settings: BlockConfiguration< RemoteDataInnerBlockAttributes > = {
			attributes: {},
			category: 'media',
			title: 'Image Block',
		};

		const result = addUsesContext( settings, 'core/image' );

		expect( result.usesContext ).toContain( REMOTE_DATA_CONTEXT_KEY );
	} );

	it( 'should handle dynamic blocks like post blocks', () => {
		mockGetSettings.mockReturnValue( {
			__experimentalBlockBindingsSupportedAttributes: {
				'core/post-title': [ 'content' ],
				'core/post-featured-image': [ 'url', 'alt' ],
			},
		} );

		const postTitleSettings: BlockConfiguration< RemoteDataInnerBlockAttributes > = {
			attributes: {},
			category: 'theme',
			title: 'Post Title',
		};

		const postImageSettings: BlockConfiguration< RemoteDataInnerBlockAttributes > = {
			attributes: {},
			category: 'theme',
			title: 'Post Featured Image',
		};

		const titleResult = addUsesContext( postTitleSettings, 'core/post-title' );
		const imageResult = addUsesContext( postImageSettings, 'core/post-featured-image' );

		expect( titleResult.usesContext ).toContain( REMOTE_DATA_CONTEXT_KEY );
		expect( imageResult.usesContext ).toContain( REMOTE_DATA_CONTEXT_KEY );
	} );

	it( 'should return settings unchanged if block editor settings are not available', () => {
		// Mock select to return undefined (store not ready yet)
		mockSelect.mockReturnValue( undefined );

		const settings: BlockConfiguration< RemoteDataInnerBlockAttributes > = {
			attributes: {},
			category: 'text',
			title: 'Test Block',
		};

		const result = addUsesContext( settings, 'core/paragraph' );

		expect( result ).toEqual( settings );
	} );

	it( 'should handle missing __experimentalBlockBindingsSupportedAttributes', () => {
		// Mock settings without the experimental property
		mockGetSettings.mockReturnValue( {} );

		const settings: BlockConfiguration< RemoteDataInnerBlockAttributes > = {
			attributes: {},
			category: 'text',
			title: 'Test Block',
		};

		const result = addUsesContext( settings, 'core/paragraph' );

		expect( result ).toEqual( settings );
	} );

	it( 'should not add context for blocks with empty attribute arrays', () => {
		mockGetSettings.mockReturnValue( {
			__experimentalBlockBindingsSupportedAttributes: {
				'core/some-block': [], // Block registered but no attributes support bindings
			},
		} );

		const settings: BlockConfiguration< RemoteDataInnerBlockAttributes > = {
			attributes: {},
			category: 'text',
			title: 'Test Block',
		};

		const result = addUsesContext( settings, 'core/some-block' );

		expect( result ).toEqual( settings );
		expect( result.usesContext ).toBeUndefined();
	} );
} );
