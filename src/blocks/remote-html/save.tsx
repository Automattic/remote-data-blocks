import { BlockSaveProps } from '@wordpress/blocks';
import { RawHTML } from '@wordpress/element';

interface RemoteDataHTMLSaveAttributes extends Record< string, unknown > {
	content?: string | StringSeriablizable;
}

export function Save( props: BlockSaveProps< RemoteDataHTMLSaveAttributes > ) {
	return <RawHTML>{ props.attributes.content?.toString() ?? '' }</RawHTML>;
}
