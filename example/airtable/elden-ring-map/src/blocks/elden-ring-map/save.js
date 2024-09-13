import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

export default function save( a, b, c ) {
	console.log( { savedContext: 1, a, b, c } );
	return (
		<p { ...useBlockProps.save() }>
			<InnerBlocks.Content />
		</p>
	);
}
