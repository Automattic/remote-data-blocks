import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import {
	BLOCK_BINDING_SOURCE,
	PATTERN_OVERRIDES_CONTEXT_KEY,
	REMOTE_DATA_CONTEXT_KEY,
} from '../../../../../src/blocks/context-container/config/constants';
import { withBlockBinding } from '../../../../../src/blocks/context-container/hooks/with-block-binding';

// Minimal mocking for WordPress dependencies
vi.mock( '@wordpress/block-editor', () => ( {
	InspectorControls: ( { children }: { children: React.ReactNode } ) => (
		<div data-testid="inspector-controls">{ children }</div>
	),
} ) );
vi.mock( '@wordpress/components', () => ( {
	PanelBody: ( { children, title }: { children: React.ReactNode; title: string } ) => (
		<div data-testid="panel-body" title={ title }>
			{ children }
		</div>
	),
} ) );

describe( 'withBlockBinding', () => {
	const MockBlockEdit = vi.fn( () => <div data-testid="mock-block-edit" /> );
	const WrappedComponent = withBlockBinding( MockBlockEdit );

	beforeEach( () => {
		vi.useFakeTimers();
	} );

	afterEach( () => {
		vi.useRealTimers();
		MockBlockEdit.mockClear();
		cleanup();
	} );

	it( 'renders original BlockEdit when no remote data is available', () => {
		render(
			<WrappedComponent
				attributes={ {} }
				context={ {} }
				name="test-block"
				setAttributes={ () => {} }
				clientId="test-client-id"
				isSelected={ false }
				className=""
			/>
		);

		expect( screen.getByTestId( 'mock-block-edit' ) ).toBeDefined();
		expect( screen.queryByTestId( 'inspector-controls' ) ).toBeNull();
	} );

	it( 'renders BoundBlockEdit when remote data is available', async () => {
		const remoteData = {
			availableBindings: { field1: { name: 'Field 1' } },
			results: [ { field1: 'value1' } ],
		};
		render(
			<WrappedComponent
				attributes={ {} }
				context={ { [ REMOTE_DATA_CONTEXT_KEY ]: remoteData } }
				name="test-block"
				setAttributes={ () => {} }
				clientId="test-client-id"
				isSelected={ false }
				className=""
			/>
		);

		await vi.runAllTimersAsync();

		expect( screen.getByTestId( 'mock-block-edit' ) ).toBeDefined();
		expect( screen.getByTestId( 'inspector-controls' ) ).toBeDefined();
		expect( screen.getByTestId( 'panel-body' ) ).toBeDefined();
	} );

	it( 'does not render BoundBlockEdit for synced pattern without enabled overrides', () => {
		const remoteData = {
			availableBindings: { field1: { name: 'Field 1' } },
			results: [ { field1: 'value1' } ],
		};
		render(
			<WrappedComponent
				attributes={ { metadata: { bindings: {} } } }
				context={ {
					[ REMOTE_DATA_CONTEXT_KEY ]: remoteData,
					[ PATTERN_OVERRIDES_CONTEXT_KEY ]: [ 'test-pattern' ],
				} }
				name="test-block"
				setAttributes={ () => {} }
				clientId="test-client-id"
				isSelected={ false }
				className=""
			/>
		);

		expect( screen.getByTestId( 'mock-block-edit' ) ).toBeDefined();
		expect( screen.queryByTestId( 'inspector-controls' ) ).toBeNull();
	} );

	it( 'updates attributes when mismatches are found', () => {
		const mockSetAttributes = vi.fn();
		const props = {
			attributes: {
				content: 'Old Title',
				metadata: {
					bindings: {
						content: {
							source: BLOCK_BINDING_SOURCE,
							args: { field: 'title' },
						},
					},
				},
			},
			context: {
				[ REMOTE_DATA_CONTEXT_KEY ]: {
					results: [ { title: 'New Title' } ],
					availableBindings: { title: { name: 'Title' } },
				},
			},
			name: 'test-block',
			setAttributes: mockSetAttributes,
			clientId: 'test-client-id',
			isSelected: false,
			className: '',
		};

		render( <WrappedComponent { ...props } /> );

		expect( MockBlockEdit ).toHaveBeenCalledTimes( 1 );
		expect( MockBlockEdit ).toHaveBeenCalledWith(
			{
				...props,
				attributes: { ...props.attributes, content: 'New Title' },
			},
			{}
		);
		expect( mockSetAttributes ).not.toHaveBeenCalled();
	} );

	it( 'does not update attributes when no mismatches are found', () => {
		const mockSetAttributes = vi.fn();
		const props = {
			attributes: {
				content: 'Matching Title',
				metadata: {
					bindings: {
						content: {
							source: BLOCK_BINDING_SOURCE,
							args: { field: 'title' },
						},
					},
				},
			},
			context: {
				[ REMOTE_DATA_CONTEXT_KEY ]: {
					results: [ { title: 'Matching Title' } ],
					availableBindings: { title: { name: 'Title' } },
				},
			},
			name: 'test-block',
			setAttributes: mockSetAttributes,
			clientId: 'test-client-id',
			isSelected: false,
			className: '',
		};

		render( <WrappedComponent { ...props } /> );

		expect( MockBlockEdit ).toHaveBeenCalledTimes( 1 );
		expect( MockBlockEdit ).toHaveBeenCalledWith( props, {} );
		expect( mockSetAttributes ).not.toHaveBeenCalled();
	} );
} );
