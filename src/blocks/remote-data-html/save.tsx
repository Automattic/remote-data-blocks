import { useBlockProps } from '@wordpress/block-editor';
import { RawHTML } from '@wordpress/element';

export default function save() {
	return <div { ...useBlockProps.save() }>
		Test save content
		{/* <RawHTML>{ attributes.content }</RawHTML>; */}
	</div>
}
