import { getBlockType } from '@wordpress/blocks';
import { select } from '@wordpress/data';
import { store as richTextStore } from '@wordpress/rich-text';
import DOMPurify from 'dompurify';

import type { BindingValue } from '@/utils/block-binding-value';

/** Sanitize editor markup independently of the WordPress version's RichText parser. */
export function sanitizeBindingHTML( html: string ): string {
	const formats = select( richTextStore ).getFormatTypes();
	return DOMPurify.sanitize( html, {
		USE_PROFILES: { html: true },
		ADD_TAGS: [
			'remote-data-blocks-inline-field',
			...formats.filter( format => format.tagName.includes( '-' ) ).map( format => format.tagName ),
		],
		ADD_ATTR: ( attribute, tag ) =>
			attribute === 'target' ||
			( ! /^(?:on|srcdoc$|is$)/i.test( attribute ) &&
				formats.some(
					format =>
						format.tagName === tag && Object.values( format.attributes ?? {} ).includes( attribute )
				) ),
	} );
}

export function sanitizeBindingValue(
	value: BindingValue,
	blockName: string | undefined,
	target: string
): BindingValue {
	if ( typeof value !== 'string' ) {
		return value;
	}
	// Remote HTML is rendered in an opaque-origin iframe, including embeds and scripts.
	if ( blockName === 'remote-data-blocks/remote-html' && target === 'content' ) {
		return value;
	}
	const schema = blockName ? getBlockType( blockName )?.attributes?.[ target ] : undefined;
	const htmlAttribute = schema?.source === 'attribute' ? schema.attribute?.toLowerCase() : '';
	const isURL =
		/(?:url|href|src)$/i.test( target ) ||
		[ 'href', 'src', 'cite', 'action', 'formaction', 'poster', 'data', 'background' ].includes(
			htmlAttribute ?? ''
		);
	if ( isURL && ! DOMPurify.isValidAttribute( 'a', 'href', value ) ) {
		return null;
	}
	const isMarkup =
		schema?.type === 'rich-text' ||
		schema?.source === 'html' ||
		[ 'content', 'text', 'caption' ].includes( target );
	return isMarkup ? sanitizeBindingHTML( value ) : value;
}

/** Clean saved inline replacements before Core puts their innerHTML in the editor. */
export function sanitizeInlineBindingAttributes(
	attributes: RemoteDataInnerBlockAttributes
): RemoteDataInnerBlockAttributes {
	const changes = Object.fromEntries(
		Object.entries( attributes ).flatMap( ( [ name, value ] ) => {
			const isRichText = value && typeof value === 'object' && 'toHTMLString' in value;
			const html = typeof value === 'string' || isRichText ? String( value ) : '';
			if ( ! html.includes( '<remote-data-blocks-inline-field' ) ) {
				return [];
			}
			const doc = document.implementation.createHTMLDocument( '' );
			doc.body.innerHTML = html;
			for ( const field of doc.body.querySelectorAll( 'remote-data-blocks-inline-field' ) ) {
				field.outerHTML = sanitizeBindingHTML( field.outerHTML );
			}
			return [ [ name, doc.body.innerHTML ] ];
		} )
	);
	return Object.keys( changes ).length ? { ...attributes, ...changes } : attributes;
}
