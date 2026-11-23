import { fireEvent, render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import {
	BlockBindingControls,
	getBindingFieldTypes,
} from '@/blocks/remote-data-container/components/BlockBindingControls';
import { BLOCK_BINDING_SOURCE } from '@/config/constants';

const block = vi.hoisted( () => ( { attributes: {} as Record< string, { type: string } > } ) );
vi.mock( '@wordpress/blocks', () => ( { getBlockType: () => block } ) );
vi.mock( '@/blocks/remote-data-container/utils/tracks', () => ( { sendTracksEvent: vi.fn() } ) );
vi.mock( '@wordpress/components', () => ( {
	SelectControl: ( {
		label,
		name,
		options,
		onChange,
		value,
	}: {
		label: string;
		name: string;
		options: { label: string; value: string }[];
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
				{ options.map( option => (
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
	it( 'keeps semantic button/image choices and excludes markup from generic text', () => {
		expect( getBindingFieldTypes( 'core/button', 'url', 'string' ) ).toEqual( [ 'button_url' ] );
		expect( getBindingFieldTypes( 'core/image', 'url', 'string' ) ).toEqual( [ 'image_url' ] );
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
