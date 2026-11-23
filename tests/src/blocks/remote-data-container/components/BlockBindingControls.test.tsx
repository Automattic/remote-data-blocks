import { fireEvent, render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import {
	BlockBindingControls,
	BlockBindingFieldControl,
	getBindingFieldTypes,
	getSuggestedBindingFieldTypes,
} from '@/blocks/remote-data-container/components/BlockBindingControls';
import { BLOCK_BINDING_SOURCE } from '@/config/constants';

import type { ReactNode } from 'react';

const block = vi.hoisted( () => ( { attributes: {} as Record< string, { type: string } > } ) );
vi.mock( '@wordpress/blocks', () => ( { getBlockType: () => block } ) );
vi.mock( '@/blocks/remote-data-container/utils/tracks', () => ( { sendTracksEvent: vi.fn() } ) );
vi.mock( '@wordpress/components', () => ( {
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
	CheckboxControl: ( { label }: { label: string } ) => (
		<label htmlFor="label-control">
			{ label }
			<input id="label-control" type="checkbox" />
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
		const control = screen.getByRole( 'combobox', { name: 'Unexpected Value' } );
		expect( control ).toHaveTextContent( 'Numeric field' );
		expect( control ).toHaveTextContent( 'Integer field' );
		expect( control ).not.toHaveTextContent( 'Text field' );
		expect( control ).not.toHaveTextContent( 'HTML field' );
		fireEvent.change( control, { target: { value: 'num' } } );
		expect( defaults.updateBinding ).toHaveBeenCalledWith( 'unexpectedValue', { field: 'num' } );
	} );
	it( 'does not offer scalar fields for nested attributes', () => {
		block.attributes.unexpectedValue = { type: 'object' };
		render( <BlockBindingControls { ...defaults } /> );
		expect( screen.getAllByRole( 'option' ) ).toHaveLength( 1 );
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
		const control = screen.getByRole( 'combobox' );
		expect( control ).toHaveValue( 'vanished' );
		expect( defaults.updateBinding ).not.toHaveBeenCalled();
		fireEvent.change( control, { target: { value: 'num' } } );
		expect( defaults.updateBinding ).toHaveBeenCalledWith( 'unexpectedValue', {
			block: 'test/data',
			field: 'num',
			previewIndex: 2,
			label: 'Existing label',
		} );
		fireEvent.change( control, { target: { value: '' } } );
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
		const text = screen.getByRole( 'combobox', { name: 'Button Text' } );
		const url = screen.getByRole( 'combobox', { name: 'Button URL' } );
		expect( text ).toHaveTextContent( 'Title' );
		expect( url ).toHaveTextContent( 'Location' );
		expect( screen.queryByRole( 'group', { name: 'Suggested fields' } ) ).toBeNull();
		fireEvent.change( text, { target: { value: 'title' } } );
		expect( defaults.updateBinding ).toHaveBeenCalledWith( 'text', { field: 'title' } );
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
		expect( screen.getByRole( 'combobox', { name: 'Raw HTML' } ) ).toHaveTextContent(
			'Markdown field'
		);
		expect( screen.queryByRole( 'checkbox' ) ).toBeNull();
	} );
} );
