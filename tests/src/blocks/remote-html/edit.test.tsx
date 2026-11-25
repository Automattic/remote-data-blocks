// @vitest-environment jsdom
import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

import { REMOTE_DATA_CONTEXT_KEY } from '@/blocks/remote-data-container/config/constants';
import { Edit } from '@/blocks/remote-html/edit';
import { BLOCK_BINDING_SOURCE } from '@/config/constants';
import { cloneBlockForTemplatePreview } from '@/utils/block-binding';
import { createRemoteDataResults } from '@/utils/remote-data';

import type { BlockEditProps } from '@wordpress/blocks';

vi.mock( '@wordpress/block-editor', () => ( {
	store: {},
	transformStyles: () => [],
	useBlockProps: () => ( {} ),
} ) );
vi.mock( '@wordpress/blocks', async importOriginal => ( {
	...( await importOriginal< typeof import('@wordpress/blocks') >() ),
	getBlockType: () => ( { attributes: { content: { type: 'string', source: 'raw' } } } ),
} ) );
vi.mock( '@wordpress/data', async importOriginal => ( {
	...( await importOriginal< typeof import('@wordpress/data') >() ),
	useSelect: ( callback: ( select: () => unknown ) => unknown ) =>
		callback( () => ( { getSettings: () => ( {} ), getPreviewIndex: () => 1 } ) ),
} ) );

const authored = '<em>Authored fallback</em>';
const live = '<strong>Live markup</strong>';
const preview = '<strong>Preview markup</strong>';

function renderPreview( content: RemoteDataBlockBinding, results?: Record< string, unknown >[] ) {
	const props: BlockEditProps< RemoteDataInnerBlockAttributes > = {
		name: 'remote-data-blocks/remote-html',
		attributes: { content: authored, metadata: { bindings: { content } } },
		clientId: 'remote-html',
		className: '',
		context: results
			? {
					[ REMOTE_DATA_CONTEXT_KEY ]: {
						resultId: 'test-results',
						results: createRemoteDataResults( results ),
					},
			  }
			: {},
		isSelected: true,
		setAttributes: vi.fn(),
	};
	render( <Edit { ...props } /> );
	return screen.getByTitle( 'Remote Data Block HTML Preview' );
}

const binding = {
	source: BLOCK_BINDING_SOURCE,
	args: { field: 'markup', block: 'test/data' },
};

describe( 'Remote HTML edit preview', () => {
	it( 'resolves the selected live row inside the opaque frame', () => {
		const frame = renderPreview( binding, [ { markup: 'First row' }, { markup: live } ] );
		expect( frame ).toHaveAttribute( 'sandbox', 'allow-scripts allow-presentation' );
		expect( frame.getAttribute( 'srcdoc' ) ).toContain( live );
		expect( screen.queryByText( 'Live markup' ) ).toBeNull();
	} );
	it( 'shows a saved preview value without live context', () => {
		const frame = renderPreview( {
			...binding,
			args: { ...binding.args, isPreview: true, previewValue: preview },
		} );
		expect( frame.getAttribute( 'srcdoc' ) ).toContain( preview );
	} );
	it( 'honors an explicit preview row ahead of the selected live row', () => {
		const frame = renderPreview( { ...binding, args: { ...binding.args, previewIndex: 0 } }, [
			{ markup: preview },
			{ markup: live },
		] );
		expect( frame.getAttribute( 'srcdoc' ) ).toContain( preview );
	} );
	it( 'uses authored content if the selected live row has no field value', () => {
		const frame = renderPreview( binding, [ { markup: 'First row' }, {} ] );
		expect( frame.getAttribute( 'srcdoc' ) ).toContain( authored );
	} );
	it( 'leaves an explicitly empty field blank', () => {
		const frame = renderPreview( binding, [ { markup: 'First row' }, { markup: '' } ] );
		expect( frame.getAttribute( 'srcdoc' ) ).not.toContain( authored );
	} );
	it.each( [
		[ 'missing', {}, authored ],
		[ 'empty', { markup: '' }, '<body><div></div></body>' ],
	] )(
		'renders the expected content for a %s optional field in a template clone',
		( _, fields, expected ) => {
			const result = createRemoteDataResults( [ fields ] )[ 0 ]!;
			const block = cloneBlockForTemplatePreview(
				{
					name: 'remote-data-blocks/remote-html',
					clientId: 'template',
					attributes: { content: authored, metadata: { bindings: { content: binding } } },
					innerBlocks: [],
					isValid: true,
				},
				result,
				binding.args.block,
				0
			);
			const clonedBinding = block.attributes
				.metadata as RemoteDataInnerBlockAttributes[ 'metadata' ];
			const frame = renderPreview( clonedBinding!.bindings!.content!, [ fields ] );
			expect( frame.getAttribute( 'srcdoc' ) ).toContain( expected );
		}
	);
	it( 'keeps the authored content for an unsupported binding source', () => {
		const frame = renderPreview( { ...binding, source: 'test/other' }, [
			{ markup: 'First row' },
			{ markup: live },
		] );
		expect( frame.getAttribute( 'srcdoc' ) ).toContain( authored );
	} );
} );
