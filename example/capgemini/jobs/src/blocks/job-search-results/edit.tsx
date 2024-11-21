import { useBlockProps } from '@wordpress/block-editor';

import './editor.scss';

export function Edit() {
	const blockProps = useBlockProps();
	return (
		<div { ...blockProps }>
			<ul>
				<li>Example search results</li>
			</ul>
		</div>
	);
}