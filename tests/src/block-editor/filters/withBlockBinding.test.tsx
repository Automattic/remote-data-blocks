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
	it( 'edits default pattern bindings in their definition and hides ineffective writes in instances', () => {
		const props = {
			attributes: {
				metadata: {
					name: 'Original pattern key',
					bindings: { __default: { source: 'core/pattern-overrides' } as never },
				},
			},
			context: {
				[ REMOTE_DATA_CONTEXT_KEY ]: {
					blockName: 'test/block',
					results: createResults( [ { field1: 'value1' } ] ),
				},
			},
			name: 'test/block',
			setAttributes: vi.fn< ( attributes: unknown ) => void >(),
			clientId: 'id',
			isSelected: false,
			className: '',
		};
		const { rerender } = render( <WrappedComponent { ...props } /> );
		expect( screen.getByTestId( 'inspector-controls' ) ).toBeDefined();
		rerender(
			<WrappedComponent
				{ ...props }
				context={ { ...props.context, [ PATTERN_OVERRIDES_CONTEXT_KEY ]: [] } }
			/>
		);
		expect( screen.queryByTestId( 'inspector-controls' ) ).toBeNull();
		expect( screen.getByTestId( 'mock-block-edit' ) ).toBeDefined();
		expect( props.setAttributes ).not.toHaveBeenCalled();
	} );

	it( 'retains editing for explicit override bindings in an instance', () => {
		render(
			<WrappedComponent
				attributes={ {
					metadata: {
						name: 'Original pattern key',
						bindings: { url: { source: 'core/pattern-overrides' } as never },
					},
				} }
				context={ {
					[ REMOTE_DATA_CONTEXT_KEY ]: {
						blockName: 'test/block',
						results: createResults( [ { field1: 'value1' } ] ),
					},
					[ PATTERN_OVERRIDES_CONTEXT_KEY ]: [],
				} }
				name="test/block"
				setAttributes={ vi.fn() }
				clientId="id"
				isSelected={ false }
				className=""
			/>
		);
		expect( screen.getByTestId( 'inspector-controls' ) ).toBeDefined();
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
			setAttributes: vi.fn< ( attributes: unknown ) => void >(),
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
	it.each( [ 'url', '__default' ] )(
		'preserves the pattern name and %s sibling through update, removal and serialization',
		target => {
			const patternBinding = { source: 'core/pattern-overrides' } as never;
			const attributes: RemoteDataInnerBlockAttributes = {
				metadata: { name: 'Original pattern key', bindings: { [ target ]: patternBinding } },
			};
			const props = {
				attributes,
				context: {
					[ REMOTE_DATA_CONTEXT_KEY ]: {
						blockName: 'test/block',
						results: createResults( [ { field1: 'value1' } ] ),
					},
				},
				name: 'test/block',
				setAttributes: vi.fn< ( attributes: unknown ) => void >(),
				clientId: 'id',
				isSelected: false,
				className: '',
			};
			const { rerender } = render( <WrappedComponent { ...props } /> );
			fireEvent.click( screen.getByText( 'Bind field' ) );
			const saved = JSON.parse(
				JSON.stringify( props.setAttributes.mock.lastCall?.[ 0 ] )
			) as RemoteDataInnerBlockAttributes;
			expect( saved.metadata?.name ).toBe( 'Original pattern key' );
			expect( saved.metadata?.bindings?.[ target ] ).toEqual( patternBinding );
			expect( saved.metadata?.bindings?.content?.args ).toEqual( {
				block: 'test/block',
				field: 'field1',
				previewIndex: 2,
			} );
			rerender( <WrappedComponent { ...props } attributes={ saved } /> );
			fireEvent.click( screen.getByText( 'Remove field' ) );
			expect( props.setAttributes ).toHaveBeenLastCalledWith( {
				metadata: { name: 'Original pattern key', bindings: { [ target ]: patternBinding } },
			} );
		}
	);

	it( 'keeps the existing list-view naming behavior for ordinary blocks', () => {
		const props = {
			attributes: { metadata: { name: 'Old name' } },
			context: {
				[ REMOTE_DATA_CONTEXT_KEY ]: {
					blockName: 'test/block',
					results: createResults( [ { field1: 'value1' } ] ),
				},
			},
			name: 'test/block',
			setAttributes: vi.fn< ( attributes: unknown ) => void >(),
			clientId: 'id',
			isSelected: false,
			className: '',
		};
		render( <WrappedComponent { ...props } /> );
		fireEvent.click( screen.getByText( 'Bind field' ) );
		expect(
			( props.setAttributes.mock.lastCall?.[ 0 ] as RemoteDataInnerBlockAttributes ).metadata?.name
		).toBe( 'Field 1' );
		fireEvent.click( screen.getByText( 'Remove field' ) );
		expect(
			( props.setAttributes.mock.lastCall?.[ 0 ] as RemoteDataInnerBlockAttributes ).metadata?.name
		).toBeUndefined();
	} );
} );
