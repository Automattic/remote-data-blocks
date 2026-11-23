import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { useSelect } from '@wordpress/data';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { withBlockBinding } from '@/block-editor/filters/withBlockBinding';
import { REMOTE_DATA_CONTEXT_KEY } from '@/blocks/remote-data-container/config/constants';
import { BLOCK_BINDING_SOURCE, PATTERN_OVERRIDES_CONTEXT_KEY } from '@/config/constants';
import { createRemoteDataResults as createResults } from '@/utils/remote-data';

const support = vi.hoisted( () => ( { attributes: [ 'content' ] as string[] } ) );
vi.mock( '@/utils/block-bindings-support', () => ( {
	useSupportedBindingAttributes: () => support.attributes,
} ) );
vi.mock( '@/blocks/remote-data-container/components/BlockBindingControls', () => ( {
	BlockBindingControls: ( {
		updateBinding,
		removeBinding,
	}: {
		updateBinding: ( target: string, args: { field: string; previewIndex: number } ) => void;
		removeBinding: ( target: string ) => void;
	} ) => (
		<>
			<button onClick={ () => updateBinding( 'content', { field: 'field1', previewIndex: 2 } ) }>
				Bind field
			</button>
			<button onClick={ () => removeBinding( 'content' ) }>Remove field</button>
		</>
	),
} ) );

vi.mock( '@/blocks/remote-data-container/utils/tracks', () => ( {
	sendTracksEvent: vi.fn(),
} ) );

// Minimal mocking for WordPress dependencies
vi.mock( '@wordpress/block-editor', () => ( {
	InspectorControls: ( { children }: { children: React.ReactNode } ) => (
		<div data-testid="inspector-controls">{ children }</div>
	),
	store: {},
} ) );

vi.mock( '@wordpress/components', () => ( {
	PanelBody: ( { children, title }: { children: React.ReactNode; title: string } ) => (
		<div data-testid="panel-body" title={ title }>
			{ children }
		</div>
	),
} ) );

vi.mock( '@wordpress/data' );

vi.mock( '@/hooks/useEditedPostAttribute', () => ( {
	useEditedPostAttribute: () => ( {
		postType: '',
	} ),
} ) );

describe( 'withBlockBinding', () => {
	const hasMultiSelection = vi.fn().mockReturnValue( false );
	const MockBlockEdit = vi.fn( () => <div data-testid="mock-block-edit" /> );
	const WrappedComponent = withBlockBinding( MockBlockEdit );
	const testBlockConfig: LocalizedBlockData = {
		config: {
			'test/block': {
				availableBindings: { field1: { name: 'Field 1', type: 'string' } },
				availableOverrides: [],
				dataSourceType: 'test-source',
				name: 'test/block',
				patterns: { default: 'test/block/pattern' },
				selectors: [],
				settings: {
					category: 'widget',
					title: 'Test block',
				},
			},
		},
		rest_url: 'http://example.com/wp-json',
	};

	beforeEach( () => {
		support.attributes = [ 'content' ];
		vi.useFakeTimers();
		vi.mocked( useSelect ).mockImplementation( () => ( { hasMultiSelection } ) );
		window.REMOTE_DATA_BLOCKS = testBlockConfig;
	} );

	afterEach( () => {
		vi.useRealTimers();
		hasMultiSelection.mockClear();
		MockBlockEdit.mockClear();
		cleanup();
	} );

	it( 'renders original BlockEdit when no remote data is available', () => {
		render(
			<WrappedComponent
				attributes={ {} }
				context={ {} }
				name="test/block"
				setAttributes={ () => {} }
				clientId="test-client-id"
				isSelected={ false }
				className=""
			/>
		);

		expect( hasMultiSelection ).not.toHaveBeenCalled();
		expect( screen.getByTestId( 'mock-block-edit' ) ).toBeDefined();
		expect( screen.queryByTestId( 'inspector-controls' ) ).toBeNull();
	} );

	it( 'renders original BlockEdit when multiple blocks are selected', () => {
		hasMultiSelection.mockReturnValueOnce( true );

		const remoteData = {
			blockName: 'test/block',
			results: createResults( [ { field1: 'value1' } ] ),
		};

		render(
			<WrappedComponent
				attributes={ {} }
				context={ { [ REMOTE_DATA_CONTEXT_KEY ]: remoteData } }
				name="test/block"
				setAttributes={ () => {} }
				clientId="test-client-id"
				isSelected={ false }
				className=""
			/>
		);

		expect( hasMultiSelection ).toHaveBeenCalledTimes( 1 );
		expect( screen.getByTestId( 'mock-block-edit' ) ).toBeDefined();
		expect( screen.queryByTestId( 'inspector-controls' ) ).toBeNull();
	} );

	it( 'renders BoundBlockEdit when remote data is available', async () => {
		const remoteData = {
			blockName: 'test/block',
			results: createResults( [ { field1: 'value1' } ] ),
		};
		render(
			<WrappedComponent
				attributes={ {} }
				context={ { [ REMOTE_DATA_CONTEXT_KEY ]: remoteData } }
				name="test/block"
				setAttributes={ () => {} }
				clientId="test-client-id"
				isSelected={ false }
				className=""
			/>
		);

		await vi.runAllTimersAsync();

		expect( hasMultiSelection ).toHaveBeenCalledTimes( 1 );
		expect( screen.getByTestId( 'mock-block-edit' ) ).toBeDefined();
		expect( screen.getByTestId( 'inspector-controls' ) ).toBeDefined();
		expect( screen.getByTestId( 'panel-body' ) ).toBeDefined();
	} );

	it( 'does not render BoundBlockEdit for synced pattern without enabled overrides', () => {
		const remoteData = {
			blockName: 'test/block',
			results: createResults( [ { field1: 'value1' } ] ),
		};
		render(
			<WrappedComponent
				attributes={ { metadata: { bindings: {} } } }
				context={ {
					[ REMOTE_DATA_CONTEXT_KEY ]: remoteData,
					[ PATTERN_OVERRIDES_CONTEXT_KEY ]: [ 'test-pattern' ],
				} }
				name="test/block"
				setAttributes={ () => {} }
				clientId="test-client-id"
				isSelected={ false }
				className=""
			/>
		);

		expect( hasMultiSelection ).toHaveBeenCalledTimes( 1 );
		expect( screen.getByTestId( 'mock-block-edit' ) ).toBeDefined();
		expect( screen.queryByTestId( 'inspector-controls' ) ).toBeNull();
	} );
	it( 'hides unsupported blocks but keeps existing bindings removable after map removal', () => {
		support.attributes = [];
		const remoteData = {
			blockName: 'test/block',
			results: createResults( [ { field1: 'value1' } ] ),
		};
		const props = {
			attributes: {},
			context: { [ REMOTE_DATA_CONTEXT_KEY ]: remoteData },
			name: 'test/block',
			setAttributes: vi.fn(),
			clientId: 'id',
			isSelected: false,
			className: '',
		};
		const { rerender } = render( <WrappedComponent { ...props } /> );
		expect( screen.queryByTestId( 'inspector-controls' ) ).toBeNull();
		rerender(
			<WrappedComponent
				{ ...props }
				attributes={ {
					metadata: {
						bindings: {
							content: {
								source: BLOCK_BINDING_SOURCE,
								args: { block: 'test/block', field: 'missing' },
							},
						},
					},
				} }
			/>
		);
		expect( screen.getByTestId( 'inspector-controls' ) ).toBeDefined();
	} );

	it( 'updates and removes only the target, preserving foreign bindings and custom metadata', () => {
		const setAttributes = vi.fn();
		const foreign = { source: 'other/source', args: { block: 'other', field: 'url' } };
		const props = {
			attributes: { metadata: { custom: 'retained', bindings: { url: foreign } } },
			context: {
				[ REMOTE_DATA_CONTEXT_KEY ]: {
					blockName: 'test/block',
					results: createResults( [ { field1: 'value1' } ] ),
				},
			},
			name: 'test/block',
			setAttributes,
			clientId: 'id',
			isSelected: false,
			className: '',
		};
		render( <WrappedComponent { ...props } /> );
		fireEvent.click( screen.getByText( 'Bind field' ) );
		expect( setAttributes ).toHaveBeenLastCalledWith(
			expect.objectContaining( {
				metadata: expect.objectContaining( {
					custom: 'retained',
					bindings: {
						url: foreign,
						content: {
							source: BLOCK_BINDING_SOURCE,
							args: { block: 'test/block', field: 'field1', previewIndex: 2 },
						},
					},
				} ),
			} )
		);
		fireEvent.click( screen.getByText( 'Remove field' ) );
		expect( setAttributes ).toHaveBeenLastCalledWith( {
			metadata: { custom: 'retained', name: undefined, bindings: { url: foreign } },
		} );
	} );
} );
