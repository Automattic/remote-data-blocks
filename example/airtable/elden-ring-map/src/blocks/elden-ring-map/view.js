import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';

import { Map } from './map';

domReady( () => {
	const elements = [
		// eslint-disable-next-line no-undef -- why does eslint think `document` is undefined here?
		...document.getElementsByClassName( 'wp-block-remote-data-blocks-elden-ring-map' ),
	];

	elements.forEach( element => {
		const context = element.dataset.wpContext;
		let coordinates = [];
		try {
			const parsed = JSON.parse( context );
			coordinates = parsed.coordinates ?? [];
		} catch ( error ) {}

		if ( ! coordinates.length ) {
			return;
		}

		const root = createRoot( element );
		root.render( <Map coordinates={ coordinates } /> );
	} );
} );
