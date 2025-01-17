import { useBlockProps } from '@wordpress/block-editor';
import { RawHTML } from '@wordpress/element';

export default function save( { attributes } ) {
	<div { ...useBlockProps.save() }>
		<RawHTML>{ attributes.content }</RawHTML>;
	</div>
}
