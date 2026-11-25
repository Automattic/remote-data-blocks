import * as matchers from '@testing-library/jest-dom/matchers';
import '@testing-library/jest-dom/vitest';
import { cleanup } from '@testing-library/react';
import { afterEach, expect } from 'vitest';

expect.extend( matchers );

// Production receives this policy from Core in the editor script localization.
window.REMOTE_DATA_BLOCKS_EDITOR_HTML = {
	attributes: {
		a: [ 'href', 'rel', 'target' ],
		em: [],
		img: [ 'src', 'alt', 'style' ],
		mark: [ 'class', 'style' ],
		p: [ 'style' ],
		span: [ 'class', 'style' ],
		strong: [],
	},
	protocols: [ 'http', 'https', 'irc', 'mailto' ],
	uriAttributes: [ 'href', 'src' ],
	styleProperties: [ 'color', 'text-decoration', 'width' ],
};

afterEach( () => {
	cleanup();
} );
