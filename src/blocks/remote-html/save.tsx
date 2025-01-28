import { BlockSaveProps } from '@wordpress/blocks';
import { RawHTML } from '@wordpress/element';

interface RemoteDataHTMLSaveAttributes {
	saveContent?: string | StringSeriablizable;
}

export function Save( props: BlockSaveProps< RemoteDataHTMLSaveAttributes > ) {
	const { attributes } = props;

	const content = attributes.saveContent ?? '';

	return <RawHTML>{ content?.toString() }</RawHTML>;
}
