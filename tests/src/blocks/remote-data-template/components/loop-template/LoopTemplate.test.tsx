import { cleanup, render } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';

import { LoopTemplate } from '@/blocks/remote-data-template/components/loop-template/LoopTemplate';

vi.mock( '@wordpress/data', () => ( {
	useDispatch: vi.fn( () => ( { setPreviewIndex: vi.fn() } ) ),
	useSelect: vi.fn(),
} ) );

describe( 'LoopTemplate', () => {
	const mockGetInnerBlocks = () => [];
	const mockRemoteData: RemoteData = {
		blockName: 'test/block',
		metadata: {},
		queryInputs: [ {} ],
		selectorQueryKey: 'test-query',
		displayQueryKey: 'test-display',
		resultId: 'test-result',
		results: [
			{
				uuid: 'foo',
				result: {
					id: { name: 'ID', type: 'id', value: '1' },
					title: { name: 'Title', type: 'string', value: 'Test 1' },
				},
			},
			{
				uuid: 'bar',
				result: {
					id: { name: 'ID', type: 'id', value: '2' },
					title: { name: 'Title', type: 'string', value: 'Test 2' },
				},
			},
		],
	};
	const expectedListItems = mockRemoteData.results.length + 1; // because of the memoized preview

	afterEach( cleanup );

	it( 'renders a list when there are more than one result', () => {
		const { container } = render(
			<LoopTemplate getInnerBlocks={ mockGetInnerBlocks } remoteData={ mockRemoteData } />
		);
		const list = container.querySelector( 'ul' );
		expect( list?.nodeName ).toBe( 'UL' );
	} );

	it( 'renders the correct number of list items', () => {
		const { container } = render(
			<LoopTemplate getInnerBlocks={ mockGetInnerBlocks } remoteData={ mockRemoteData } />
		);
		const listItems = container.querySelectorAll( 'ul > li' );
		expect( listItems.length ).toBe( expectedListItems );
	} );
} );
