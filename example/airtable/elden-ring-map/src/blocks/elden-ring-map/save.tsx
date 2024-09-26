import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

export function Save() {
	return (
		<p { ...useBlockProps.save() }>
			<InnerBlocks.Content />
		</p>
	);
}
