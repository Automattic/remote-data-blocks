// @vitest-environment jsdom
import { fireEvent, render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import { IsolatedPreview } from '@/blocks/remote-html/IsolatedPreview';

describe( 'Remote HTML preview', () => {
	it( 'isolates active markup while preserving embed scripts and styling', () => {
		const html = '<p>Preview</p><script type="application/json">{"embed":true}</script>';
		render(
			<IsolatedPreview html={ html } styles={ [ 'p{color:red}' ] } title="Remote preview" />
		);
		const frame = screen.getByTitle( 'Remote preview' );
		expect( frame ).toHaveAttribute( 'sandbox', 'allow-scripts allow-presentation' );
		expect( frame.getAttribute( 'srcdoc' ) ).toContain( html );
		expect( frame.getAttribute( 'srcdoc' ) ).toContain( 'p{color:red}' );
		expect( frame.getAttribute( 'srcdoc' ) ).toContain( 'ResizeObserver' );
		expect( frame ).toHaveAttribute( 'tabindex', '-1' );
	} );
	it( 'accepts dimensions only from its own frame and removes its listener on unmount', () => {
		const { unmount } = render(
			<IsolatedPreview html="<p>Preview</p>" styles={ [] } title="Remote preview" />
		);
		const frame = screen.getByTitle< HTMLIFrameElement >( 'Remote preview' );
		const data = { action: 'rdb-preview-resize', height: 240.5 };
		fireEvent( window, new MessageEvent( 'message', { data } ) );
		expect( frame ).toHaveAttribute( 'height', '150' );
		fireEvent( window, new MessageEvent( 'message', { source: frame.contentWindow, data } ) );
		expect( frame ).toHaveAttribute( 'height', '241' );
		fireEvent(
			window,
			new MessageEvent( 'message', {
				source: frame.contentWindow,
				data: { ...data, height: Infinity },
			} )
		);
		expect( frame ).toHaveAttribute( 'height', '241' );
		fireEvent(
			window,
			new MessageEvent( 'message', {
				source: frame.contentWindow,
				data: { ...data, height: 9000 },
			} )
		);
		expect( frame ).toHaveAttribute( 'height', '2000' );
		unmount();
		window.dispatchEvent( new MessageEvent( 'message', { source: frame.contentWindow, data } ) );
	} );
} );
