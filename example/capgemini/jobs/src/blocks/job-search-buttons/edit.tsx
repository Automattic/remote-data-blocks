import { useBlockProps } from '@wordpress/block-editor';

import './editor.scss';

export function Edit() {
	const blockProps = useBlockProps();
	return (
		<div { ...blockProps }>
			<fieldset>
				<button type="submit">Search</button>
			</fieldset>
		</div>
	);
}
