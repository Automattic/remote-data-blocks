import { useBlockProps } from '@wordpress/block-editor';

import './editor.scss';

export function Edit() {
	const blockProps = useBlockProps();
	return (
		<div { ...blockProps }>
			<fieldset>
				<legend>Search</legend>
				<input type="text" placeholder="Search" />
			</fieldset>
		</div>
	);
}
