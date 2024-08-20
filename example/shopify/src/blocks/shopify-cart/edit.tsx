import { useBlockProps } from '@wordpress/block-editor';

import './editor.scss';

export function Edit() {
	const blockProps = useBlockProps();
	return (
		<div { ...blockProps } className="remote-data-blocks-shopify-cart">
			<span className="remote-data-blocks-shopify-cart-count">0</span> Cart
		</div>
	);
}
