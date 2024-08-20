import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';

import SettingsPage from './SettingsPage';

import './index.scss';

domReady( () => {
	const el = document.getElementById( 'remote-data-blocks-settings' );
	if ( ! el ) {
		return;
	}
	const root = createRoot( el );
	root.render( <SettingsPage /> );
} );
