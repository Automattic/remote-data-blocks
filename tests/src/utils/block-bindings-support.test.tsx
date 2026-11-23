import { renderHook } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { addUsesContext } from '@/block-editor/filters/addUsesContext';
import { REMOTE_DATA_CONTEXT_KEY } from '@/blocks/remote-data-container/config/constants';
import {
	getSupportedBindingAttributes,
	useSupportedBindingAttributes,
} from '@/utils/block-bindings-support';

const runtime = vi.hoisted( () => ( { settings: {} as Record< string, unknown > } ) );
vi.mock( '@wordpress/block-editor', () => ( { store: {} } ) );
vi.mock( '@wordpress/data', () => ( {
	useSelect: ( callback: ( select: () => object ) => unknown ) =>
		callback( () => ( { getSettings: () => runtime.settings } ) ),
} ) );

describe( 'binding capabilities', () => {
	beforeEach( () => {
		runtime.settings = {};
	} );
	it( 'uses the verified 6.7 list only when the map is absent', () => {
		expect( getSupportedBindingAttributes( {}, 'core/image' ) ).toEqual( [
			'id',
			'url',
			'title',
			'alt',
		] );
		expect( getSupportedBindingAttributes( {}, 'core/button' ) ).toEqual( [
			'url',
			'text',
			'linkTarget',
			'rel',
		] );
		expect( getSupportedBindingAttributes( {}, 'core/list-item' ) ).toEqual( [] );
	} );
	it( 'honors authoritative empty maps, removals and explicit empty arrays', () => {
		expect(
			getSupportedBindingAttributes(
				{ __experimentalBlockBindingsSupportedAttributes: {} },
				'core/image'
			)
		).toEqual( [] );
		expect(
			getSupportedBindingAttributes(
				{ __experimentalBlockBindingsSupportedAttributes: { 'core/image': [] } },
				'core/image'
			)
		).toEqual( [] );
	} );
	it.each( [ [], [ 'content' ], false, 'invalid' ] )(
		'does not resurrect legacy support for a present map %j',
		map => {
			expect(
				getSupportedBindingAttributes(
					{ __experimentalBlockBindingsSupportedAttributes: map },
					'core/image'
				)
			).toEqual( [] );
		}
	);
	it( 'retains the minimum-runtime fallback when the setting is null', () => {
		expect(
			getSupportedBindingAttributes(
				{ __experimentalBlockBindingsSupportedAttributes: null },
				'core/image'
			)
		).toEqual( [ 'id', 'url', 'title', 'alt' ] );
	} );
	it( 'normalizes custom fields and observes settings arriving after registration', () => {
		const { result, rerender } = renderHook( () => useSupportedBindingAttributes( 'test/custom' ) );
		expect( result.current ).toEqual( [] );
		runtime.settings = {
			__experimentalBlockBindingsSupportedAttributes: {
				'test/custom': [ 'extraValue', 'extraValue', '', null, 1 ],
			},
		};
		rerender();
		expect( result.current ).toEqual( [ 'extraValue' ] );
		runtime.settings = { __experimentalBlockBindingsSupportedAttributes: {} };
		rerender();
		expect( result.current ).toEqual( [] );
	} );
	it( 'keeps Remote HTML isolated from generic core support', () => {
		expect(
			getSupportedBindingAttributes(
				{ __experimentalBlockBindingsSupportedAttributes: {} },
				'remote-data-blocks/remote-html'
			)
		).toEqual( [ 'content' ] );
	} );
	it( 'injects context once before capabilities are available, preserving other context', () => {
		const settings = {
			name: 'test/custom',
			title: 'Custom',
			category: 'text',
			attributes: {},
			usesContext: [ 'postId' ],
		};
		const updated = addUsesContext( settings, 'test/custom' );
		expect( updated.usesContext ).toEqual( [ 'postId', REMOTE_DATA_CONTEXT_KEY ] );
		expect( addUsesContext( updated, 'test/custom' ) ).toBe( updated );
	} );
} );
