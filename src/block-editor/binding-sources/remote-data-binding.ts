import { registerBlockBindingsSource } from '@wordpress/blocks';

registerBlockBindingsSource( {
	name: 'remote-data/binding',
	label: 'Remote Data Blocks',
	usesContext: [ 'remote-data-blocks/remoteData' ],
	getValues() {
		return {};
	},
} );
