import { cleanup, render } from '@testing-library/react';
import { afterEach, describe, expect, it } from 'vitest';

import { LoopTemplateInnerBlocks } from '@/blocks/remote-data-container/components/loop-template/LoopTemplateInnerBlocks';

describe( 'LoopTemplateInnerBlocks', () => {
	afterEach( cleanup );

	it( 'renders null when isActive is false', () => {
		const { container } = render( <LoopTemplateInnerBlocks isActive={ false } /> );
		expect( container.firstChild ).toBeNull();
	} );

	it( 'renders a li when isActive is true', () => {
		const { container } = render( <LoopTemplateInnerBlocks isActive={ true } /> );
		const element = container.firstChild;
		expect( element?.nodeName ).toBe( 'LI' );
	} );
} );
