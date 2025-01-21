import { BlockSaveProps } from '@wordpress/blocks';
import { RawHTML } from '@wordpress/element';

interface RemoteDataHTMLSaveAttributes {
	content?: string;
}

export default function save( props: BlockSaveProps< RemoteDataHTMLSaveAttributes > ) {
	const { attributes } = props;

	const content = attributes.content ?? '';

	return <RawHTML>{ content }</RawHTML>;
}
