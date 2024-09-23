import { BlockControls, useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import React from 'react';

export function Edit( { attributes } ) {
	const blockProps = useBlockProps();
	const innerBlocksProps = useInnerBlocksProps( blockProps );

	return (
		<>
			<BlockControls />
			<div { ...innerBlocksProps }>
				<p>The product type is: { attributes.shopify_product_type }</p>
			</div>
		</>
	);
}
