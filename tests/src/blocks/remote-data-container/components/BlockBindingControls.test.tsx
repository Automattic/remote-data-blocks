import { fireEvent, render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import {
	BlockBindingControls,
	BlockBindingFieldControl,
	getBindingFieldTypes,
	getSuggestedBindingFieldTypes,
} from '@/blocks/remote-data-container/components/BlockBindingControls';
import { BLOCK_BINDING_SOURCE } from '@/config/constants';

import type { ButtonHTMLAttributes, ReactNode } from 'react';

const block: { attributes: Record< string, { type: string } > } = vi.hoisted( () => ( {
	attributes: {},
} ) );
vi.mock( '@wordpress/blocks', () => ( { getBlockType: () => block } ) );
vi.mock( '@/blocks/remote-data-container/utils/tracks', () => ( { sendTracksEvent: vi.fn() } ) );
vi.mock( '@wordpress/components', () => ( {
	Button: ( {
		variant: _variant,
		size: _size,
		icon: _icon,
		showTooltip: _showTooltip,
		label: _label,
		...props
	}: ButtonHTMLAttributes< HTMLButtonElement > & {
		variant?: string;
		size?: string;
		icon?: ReactNode;
		showTooltip?: boolean;
		label?: string;
	} ) => <button { ...props } />,
	Popover: ( {
		children,
		onClose,
		'aria-labelledby': labelledBy,
	}: {
		children: ReactNode;
		onClose: () => void;
		'aria-labelledby': string;
	} ) => (
		<div
			role="dialog"
			aria-labelledby={ labelledBy }
			onKeyDownCapture={ event => {
				if ( event.key === 'Escape' ) {
					onClose();
				}
			} }
		>
			{ children }
		</div>
	),
	SelectControl: ( {
		label,
		name,
		options,
		children,
		onChange,
		value,
	}: {
		label: string;
		name: string;
		options?: { label: string; value: string }[];
		children?: ReactNode;
		onChange: ( value: string ) => void;
		value: string;
	} ) => (
		<label htmlFor={ name }>
			{ label }
			<select
				id={ name }
				name={ name }
				value={ value }
				onChange={ event => onChange( event.target.value ) }
			>
				{ children ??
					options?.map( option => (
						<option key={ option.value } value={ option.value }>
							{ option.label }
						</option>
					) ) }
			</select>
		</label>
	),
	CheckboxControl: ( {
		label,
		checked,
		onChange,
	}: {
		label: string;
		checked: boolean;
		onChange: ( checked: boolean ) => void;
	} ) => (
		<label htmlFor="label-control">
			{ label }
			<input
				id="label-control"
				type="checkbox"
				checked={ checked }
				onChange={ event => onChange( event.target.checked ) }
			/>
		</label>
	),
} ) );

const availableBindings: AvailableBindings = {
	text: { name: 'Text field', type: 'string' },
	num: { name: 'Numeric field', type: 'number' },
	int: { name: 'Integer field', type: 'integer' },
	bool: { name: 'Boolean field', type: 'boolean' },
	html: { name: 'HTML field', type: 'html' },
	markdown: { name: 'Markdown field', type: 'markdown' },
	button: { name: 'Button link', type: 'button_url' },
	image: { name: 'Image link', type: 'image_url' },
};
const defaults = {
	attributes: {},
	availableBindings,
	blockName: 'test/custom',
	remoteDataName: 'test/data',
	supportedAttributes: [ 'unexpectedValue' ],
	removeBinding: vi.fn(),
	updateBinding: vi.fn(),
};

describe( 'generic binding controls', () => {
	beforeEach( () => {
		block.attributes = { unexpectedValue: { type: 'number' } };
		vi.clearAllMocks();
	} );
	it( 'enumerates an unknown attribute from the schema and offers only compatible types', () => {
		render( <BlockBindingControls { ...defaults } /> );
		expect( screen.queryByRole( 'combobox' ) ).toBeNull();
		fireEvent.click( screen.getByRole( 'button', { name: 'Bind Unexpected Value' } ) );
		const control = screen.getByRole( 'combobox', { name: 'Remote field' } );
		expect( control ).toHaveTextContent( 'Numeric field' );
		expect( control ).toHaveTextContent( 'Integer field' );
		expect( control ).not.toHaveTextContent( 'Text field' );
		expect( control ).not.toHaveTextContent( 'HTML field' );
		fireEvent.change( control, { target: { value: 'num' } } );
		expect( defaults.updateBinding ).not.toHaveBeenCalled();
		fireEvent.click( screen.getByRole( 'button', { name: 'Apply' } ) );
		expect( defaults.updateBinding ).toHaveBeenCalledWith( 'unexpectedValue', { field: 'num' } );
	} );
	it( 'does not offer scalar fields for nested attributes', () => {
		block.attributes.unexpectedValue = { type: 'object' };
		render( <BlockBindingControls { ...defaults } /> );
		fireEvent.click( screen.getByRole( 'button', { name: 'Bind Unexpected Value' } ) );
		expect( screen.queryByRole( 'combobox' ) ).toBeNull();
		expect(
			screen.getByText( 'No matching fields are available for this attribute.' )
		).toBeVisible();
	} );
	it( 'treats semantic button/image types as suggestions, retaining scalar and markup boundaries', () => {
		expect( getSuggestedBindingFieldTypes( 'core/button', 'url' ) ).toEqual( [ 'button_url' ] );
		expect( getSuggestedBindingFieldTypes( 'core/image', 'url' ) ).toEqual( [ 'image_url' ] );
		expect( getBindingFieldTypes( 'core/button', 'url', 'string' ) ).toContain( 'string' );
		expect( getBindingFieldTypes( 'core/image', 'alt', 'string' ) ).toContain( 'string' );
		expect( getBindingFieldTypes( 'core/paragraph', 'content', 'rich-text' ) ).toContain( 'url' );
		expect( getBindingFieldTypes( 'core/button', 'url', 'object' ) ).toEqual( [] );
		expect( getBindingFieldTypes( 'test/custom', 'url', 'string' ) ).not.toContain( 'html' );
		expect( getBindingFieldTypes( 'test/custom', 'url', 'string' ) ).not.toContain( 'markdown' );
		expect( getBindingFieldTypes( 'test/custom', 'enabled', 'boolean' ) ).toEqual( [ 'boolean' ] );
	} );
	it( 'offers booleans only for boolean targets, avoiding PHP/editor text-conversion differences', () => {
		block.attributes.unexpectedValue = { type: 'string' };
		const { rerender } = render( <BlockBindingControls { ...defaults } /> );
		fireEvent.click( screen.getByRole( 'button', { name: 'Bind Unexpected Value' } ) );
		expect( screen.getByRole( 'combobox' ) ).not.toHaveTextContent( 'Boolean field' );
		block.attributes.unexpectedValue = { type: 'boolean' };
		rerender( <BlockBindingControls { ...defaults } /> );
		expect( screen.getByRole( 'combobox' ) ).toHaveTextContent( 'Boolean field' );
		expect( screen.getByRole( 'combobox' ) ).not.toHaveTextContent( 'Text field' );
		expect( getBindingFieldTypes( 'test/custom', 'value', 'rich-text' ) ).not.toContain(
			'boolean'
		);
	} );
	it( 'preserves existing args when changing a field, and lets vanished fields be removed', () => {
		const attributes = {
			metadata: {
				bindings: {
					unexpectedValue: {
						source: BLOCK_BINDING_SOURCE,
						args: {
							block: 'test/data',
							field: 'vanished',
							previewIndex: 2,
							label: 'Existing label',
						},
					},
				},
			},
		};
		render( <BlockBindingControls { ...defaults } attributes={ attributes } /> );
		expect( screen.getByText( 'vanished' ) ).toBeVisible();
		expect( defaults.updateBinding ).not.toHaveBeenCalled();
		fireEvent.click( screen.getByRole( 'button', { name: 'Change Unexpected Value binding' } ) );
		const control = screen.getByRole( 'combobox' );
		expect( control ).toHaveValue( 'vanished' );
		expect( defaults.updateBinding ).not.toHaveBeenCalled();
		fireEvent.change( control, { target: { value: 'num' } } );
		expect( defaults.updateBinding ).not.toHaveBeenCalled();
		fireEvent.click( screen.getByRole( 'button', { name: 'Apply' } ) );
		expect( defaults.updateBinding ).toHaveBeenCalledWith( 'unexpectedValue', {
			block: 'test/data',
			field: 'num',
			previewIndex: 2,
			label: 'Existing label',
		} );
		fireEvent.click( screen.getByRole( 'button', { name: 'Remove Unexpected Value binding' } ) );
		expect( defaults.removeBinding ).toHaveBeenCalledWith( 'unexpectedValue' );
	} );
	it( 'groups suggested fields first without hiding compatible fields or duplicating them', () => {
		render(
			<BlockBindingFieldControl
				availableBindings={ availableBindings }
				fieldTypes={ getBindingFieldTypes( 'core/button', 'url', 'string' ) }
				suggestedFieldTypes={ getSuggestedBindingFieldTypes( 'core/button', 'url' ) }
				label="Button URL"
				target="url"
				updateFieldBinding={ defaults.updateBinding }
				value="text"
			/>
		);
		const groups = screen.getAllByRole( 'group' );
		expect( groups.map( group => group.getAttribute( 'label' ) ) ).toEqual( [
			'Suggested fields',
			'Other compatible fields',
		] );
		expect( groups[ 0 ] ).toHaveTextContent( 'Button link' );
		expect( groups[ 0 ] ).not.toHaveTextContent( 'Text field' );
		expect( groups[ 1 ] ).toHaveTextContent( 'Text field' );
		expect( screen.getByRole( 'combobox' ) ).toHaveValue( 'text' );
		expect( screen.getAllByRole( 'option', { name: 'Button link' } ) ).toHaveLength( 1 );
		expect( screen.queryByRole( 'option', { name: 'HTML field' } ) ).toBeNull();
		expect( defaults.updateBinding ).not.toHaveBeenCalled();
	} );
	it( 'offers Conference Event string fields even with no semantic recommendations', () => {
		block.attributes = { text: { type: 'rich-text' }, url: { type: 'string' } };
		render(
			<BlockBindingControls
				{ ...defaults }
				blockName="core/button"
				availableBindings={ {
					id: { name: 'Record ID', type: 'id' },
					title: { name: 'Title', type: 'string' },
					location: { name: 'Location', type: 'string' },
				} }
				supportedAttributes={ [ 'text', 'url' ] }
			/>
		);
		expect( screen.queryByRole( 'combobox' ) ).toBeNull();
		fireEvent.click( screen.getByRole( 'button', { name: 'Bind Button Text' } ) );
		const text = screen.getByRole( 'combobox', { name: 'Remote field' } );
		expect( text ).toHaveTextContent( 'Title' );
		expect( screen.queryByRole( 'group', { name: 'Suggested fields' } ) ).toBeNull();
		fireEvent.change( text, { target: { value: 'title' } } );
		expect( defaults.updateBinding ).not.toHaveBeenCalled();
		fireEvent.click( screen.getByRole( 'button', { name: 'Apply' } ) );
		expect( defaults.updateBinding ).toHaveBeenCalledExactlyOnceWith( 'text', { field: 'title' } );
		fireEvent.click( screen.getByRole( 'button', { name: 'Bind Button URL' } ) );
		expect( screen.getByRole( 'combobox', { name: 'Remote field' } ) ).toHaveTextContent(
			'Location'
		);
		expect( screen.getAllByRole( 'combobox' ) ).toHaveLength( 1 );
		fireEvent.click( screen.getByRole( 'button', { name: 'Cancel' } ) );
		expect( defaults.updateBinding ).toHaveBeenCalledTimes( 1 );
	} );
	it( 'keeps unavailable or schema-changed selections in a separate current group', () => {
		render(
			<BlockBindingFieldControl
				availableBindings={ availableBindings }
				fieldTypes={ [ 'string' ] }
				label="Value"
				target="value"
				updateFieldBinding={ defaults.updateBinding }
				value="html"
			/>
		);
		expect( screen.getByRole( 'group', { name: 'Current binding' } ) ).toHaveTextContent(
			'HTML field'
		);
		expect( screen.getByRole( 'combobox' ) ).toHaveValue( 'html' );
		expect( defaults.updateBinding ).not.toHaveBeenCalled();
	} );
	it( 'does not add label controls to Remote HTML', () => {
		render(
			<BlockBindingControls
				{ ...defaults }
				blockName="remote-data-blocks/remote-html"
				supportedAttributes={ [ 'content' ] }
			/>
		);
		fireEvent.click( screen.getByRole( 'button', { name: 'Bind Raw HTML' } ) );
		expect( screen.getByRole( 'combobox', { name: 'Remote field' } ) ).toHaveTextContent(
			'Markdown field'
		);
		expect( screen.queryByRole( 'checkbox' ) ).toBeNull();
	} );
} );

describe( 'compact binding editor', () => {
	beforeEach( () => {
		block.attributes = { unexpectedValue: { type: 'number' }, other: { type: 'number' } };
		vi.clearAllMocks();
	} );
	it( 'keeps drafts local, cancels without a write and opens only one target', () => {
		render(
			<BlockBindingControls
				{ ...defaults }
				supportedAttributes={ [ 'unexpectedValue', 'other' ] }
			/>
		);
		const trigger = screen.getByRole( 'button', { name: 'Bind Unexpected Value' } );
		fireEvent.click( trigger );
		fireEvent.change( screen.getByRole( 'combobox' ), { target: { value: 'num' } } );
		fireEvent.click( screen.getByRole( 'button', { name: 'Cancel' } ) );
		expect( screen.queryByRole( 'combobox' ) ).toBeNull();
		expect( trigger ).toHaveFocus();
		fireEvent.click( trigger );
		expect( screen.getByRole( 'combobox' ) ).toHaveValue( '' );
		fireEvent.click( screen.getByRole( 'button', { name: 'Bind Other' } ) );
		expect( screen.getAllByRole( 'combobox' ) ).toHaveLength( 1 );
		fireEvent.keyDown( screen.getByRole( 'dialog' ), { key: 'Escape' } );
		expect( screen.queryByRole( 'dialog' ) ).toBeNull();
		expect( defaults.updateBinding ).not.toHaveBeenCalled();
		expect( defaults.removeBinding ).not.toHaveBeenCalled();
	} );
	it( 'discards an open draft when the remote data context changes', () => {
		const { rerender } = render( <BlockBindingControls { ...defaults } /> );
		fireEvent.click( screen.getByRole( 'button', { name: 'Bind Unexpected Value' } ) );
		fireEvent.change( screen.getByRole( 'combobox' ), { target: { value: 'num' } } );
		rerender( <BlockBindingControls { ...defaults } remoteDataName="test/other" /> );
		expect( screen.queryByRole( 'dialog' ) ).toBeNull();
		expect( defaults.updateBinding ).not.toHaveBeenCalled();
	} );
	it( 'retains unsupported bindings and replaces another source only after Apply', () => {
		const attributes = {
			metadata: {
				bindings: {
					removedTarget: {
						source: BLOCK_BINDING_SOURCE,
						args: { block: 'test/data', field: 'text' },
					},
					unexpectedValue: {
						source: 'core/post-meta',
						args: { field: 'external', block: 'test/data' },
					},
				},
			},
		};
		render( <BlockBindingControls { ...defaults } attributes={ attributes } /> );
		expect( screen.getByText( 'Managed by another binding' ) ).toBeVisible();
		expect(
			screen.getByRole( 'button', { name: 'Bind Unexpected Value' } )
		).toHaveAccessibleDescription( /Managed by another binding/ );
		expect(
			screen.getByRole( 'button', { name: 'Change Removed Target binding' } )
		).toHaveAccessibleDescription( /Text field/ );
		fireEvent.click( screen.getByRole( 'button', { name: 'Bind Unexpected Value' } ) );
		fireEvent.change( screen.getByRole( 'combobox' ), { target: { value: 'num' } } );
		fireEvent.click( screen.getByRole( 'button', { name: 'Cancel' } ) );
		expect( defaults.updateBinding ).not.toHaveBeenCalled();
		expect( defaults.removeBinding ).not.toHaveBeenCalled();
		fireEvent.click( screen.getByRole( 'button', { name: 'Bind Unexpected Value' } ) );
		fireEvent.change( screen.getByRole( 'combobox' ), { target: { value: 'num' } } );
		fireEvent.click( screen.getByRole( 'button', { name: 'Apply' } ) );
		expect( defaults.updateBinding ).toHaveBeenCalledExactlyOnceWith( 'unexpectedValue', {
			field: 'num',
		} );
		vi.clearAllMocks();
		fireEvent.click( screen.getByRole( 'button', { name: 'Change Removed Target binding' } ) );
		expect( screen.getByRole( 'dialog', { name: 'Change Removed Target binding' } ) ).toBeVisible();
		expect( screen.getByRole( 'combobox' ) ).toHaveValue( 'text' );
		expect( screen.getAllByRole( 'option' ) ).toHaveLength( 2 );
		fireEvent.click( screen.getByRole( 'button', { name: 'Cancel' } ) );
		fireEvent.click( screen.getByRole( 'button', { name: 'Remove Removed Target binding' } ) );
		expect( defaults.removeBinding ).toHaveBeenCalledExactlyOnceWith( 'removedTarget' );
		expect( defaults.updateBinding ).not.toHaveBeenCalled();
	} );
	it( 'only shows the existing label control for bound paragraph content', () => {
		const { rerender } = render(
			<BlockBindingControls
				{ ...defaults }
				blockName="core/paragraph"
				supportedAttributes={ [ 'content' ] }
			/>
		);
		expect( screen.queryByRole( 'checkbox' ) ).toBeNull();
		rerender(
			<BlockBindingControls
				{ ...defaults }
				blockName="core/paragraph"
				supportedAttributes={ [ 'content' ] }
				attributes={ {
					metadata: {
						bindings: {
							content: {
								source: BLOCK_BINDING_SOURCE,
								args: { block: 'test/data', field: 'text', label: 'Custom label' },
							},
						},
					},
				} }
			/>
		);
		expect( screen.getByRole( 'checkbox' ) ).toBeVisible();
		expect( screen.getByRole( 'checkbox' ) ).toBeChecked();
		expect( defaults.updateBinding ).not.toHaveBeenCalled();
		fireEvent.click( screen.getByRole( 'checkbox' ) );
		expect( defaults.updateBinding ).toHaveBeenCalledExactlyOnceWith( 'content', {
			block: 'test/data',
			field: 'text',
			label: undefined,
		} );
	} );
} );
