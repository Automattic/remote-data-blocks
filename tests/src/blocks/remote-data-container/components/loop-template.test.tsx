import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it } from 'vitest';

import { LoopTemplate } from '../../../../../src/blocks/remote-data-container/components/loop-template';

describe( 'LoopTemplate', () => {
	const mockGetInnerBlocks = () => [];
	const mockRemoteData: RemoteData = {
		blockName: 'test-block',
		isCollection: true,
		metadata: {},
		queryInput: {},
		resultId: 'test-result',
		results: [
			{ id: '1', title: 'Test 1' },
			{ id: '2', title: 'Test 2' },
		],
	};
	const expectedListItems = mockRemoteData.results.length + 1; // because of the memoized preview

	afterEach( cleanup );

	it( 'renders "No results found" when there are no results', () => {
		const emptyRemoteData: RemoteData = {
			blockName: 'test-block',
			isCollection: true,
			metadata: {},
			queryInput: {},
			resultId: 'test-result',
			results: [],
		};
		render( <LoopTemplate getInnerBlocks={ mockGetInnerBlocks } remoteData={ emptyRemoteData } /> );
		expect( screen.getByText( 'No results found.' ) ).toBeInTheDocument();
	} );

	it( 'renders a list when there are results', () => {
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
