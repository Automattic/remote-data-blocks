/* global wp */
( function () {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, RichText } = wp.blockEditor;
	const { createElement: el } = wp.element;

	registerBlockType( 'rdb-bindings-demo/static-card', {
		apiVersion: 3,
		title: 'Garden note (static)',
		category: 'widgets',
		icon: 'carrot',
		attributes: {
			botanicalNote: {
				type: 'string',
				source: 'html',
				selector: 'p',
				default: 'Choose a remote field',
			},
		},
		edit: function StaticCardEdit( { attributes, setAttributes } ) {
			return el(
				'div',
				useBlockProps(),
				el( RichText, {
					tagName: 'p',
					className: 'botanical-note',
					value: attributes.botanicalNote,
					onChange: botanicalNote => setAttributes( { botanicalNote } ),
				} )
			);
		},
		save: function ( { attributes } ) {
			return el(
				'div',
				useBlockProps.save(),
				el( RichText.Content, {
					tagName: 'p',
					className: 'botanical-note',
					value: attributes.botanicalNote,
				} )
			);
		},
	} );

	registerBlockType( 'rdb-bindings-demo/stock-card', {
		apiVersion: 3,
		title: 'Garden stock (dynamic)',
		category: 'widgets',
		icon: 'chart-bar',
		attributes: {
			plantCount: { type: 'number', default: 12 },
			isAvailable: { type: 'boolean', default: true },
		},
		edit: function StockCardEdit( { attributes } ) {
			return el(
				'div',
				useBlockProps(),
				el( 'p', {}, 'Remaining plants: ', el( 'strong', {}, String( attributes.plantCount ) ) ),
				el( 'p', {}, 'Available today: ', el( 'strong', {}, String( attributes.isAvailable ) ) )
			);
		},
		save: () => null,
	} );
} )();
