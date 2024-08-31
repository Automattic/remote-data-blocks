import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

export function Save() {
	const blockProps = useBlockProps.save();
	return (
		<div { ...blockProps }>
			<InnerBlocks.Content />
		</div>
	);
}
