import { useBlockProps } from '@wordpress/block-editor';

import './editor.scss';

export function Edit() {
	const blockProps = useBlockProps();
	return (
		<div { ...blockProps } className="remote-data-blocks-shopify-cart-button">
			<button onClick={ () => {} }>Add to Cart</button>
		</div>
	);
}
