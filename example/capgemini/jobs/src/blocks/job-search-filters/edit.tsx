import { useBlockProps } from '@wordpress/block-editor';

import './editor.scss';

export function Edit() {
	const blockProps = useBlockProps();
	return (
		<div { ...blockProps }>
			<fieldset>
				<legend>Category</legend>
				<input type="checkbox" disabled /> Result filters
			</fieldset>
		</div>
	);
}
