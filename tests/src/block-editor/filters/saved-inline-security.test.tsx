// @vitest-environment jsdom
import { render } from '@testing-library/react';
import { expect, it, vi } from 'vitest';

import { withBlockBinding } from '@/block-editor/filters/withBlockBinding';

import type { BlockEditProps } from '@wordpress/blocks';

vi.mock( '@wordpress/block-editor', () => ( { InspectorControls: () => null, store: {} } ) );
vi.mock( '@wordpress/components', () => ( { PanelBody: () => null } ) );
vi.mock( '@wordpress/data', () => ( {
	useSelect: () => ( { hasMultiSelection: () => false } ),
	select: () => ( { getFormatTypes: () => [] } ),
} ) );
vi.mock( '@/utils/block-bindings-support', () => ( { useSupportedBindingAttributes: () => [] } ) );
vi.mock( '@/utils/localized-block-data', () => ( { getBlockAvailableBindings: () => ( {} ) } ) );
vi.mock( '@/blocks/remote-data-container/hooks/useRemoteDataContext', () => ( {
	useRemoteDataContext: () => ( {} ),
} ) );
vi.mock( '@/blocks/remote-data-container/components/BlockBindingControls', () => ( {
	BlockBindingControls: () => null,
} ) );

it( 'sanitizes saved inline fields before an unbound paragraph reaches its edit component', () => {
	const edit = vi.fn( ( _props: BlockEditProps< RemoteDataInnerBlockAttributes > ) => <div /> );
	const Wrapped = withBlockBinding( edit );
	const content =
		'<user-mention data-user="42">Alice</user-mention> <remote-data-blocks-inline-field data-query="{}"><em>Saved</em><iframe srcdoc="inert"></iframe></remote-data-blocks-inline-field>';
	const attributes = { content };
	render(
		<Wrapped
			name="core/paragraph"
			attributes={ attributes }
			context={ {} }
			clientId="test"
			setAttributes={ vi.fn() }
			isSelected={ false }
			className=""
		/>
	);
	const props = edit.mock.calls[ 0 ]![ 0 ];
	expect( props.attributes.content ).toBe(
		'<user-mention data-user="42">Alice</user-mention> <remote-data-blocks-inline-field data-query="{}"><em>Saved</em></remote-data-blocks-inline-field>'
	);
	expect( attributes.content ).toBe( content );
} );
