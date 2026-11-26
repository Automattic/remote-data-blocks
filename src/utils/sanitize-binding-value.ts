import { getBlockType } from '@wordpress/blocks';
import { select } from '@wordpress/data';
import { store as richTextStore } from '@wordpress/rich-text';
import createDOMPurify from 'dompurify';

import type { BindingValue } from '@/utils/block-binding-value';

const DOMPurify = createDOMPurify();
const INLINE_FIELD = 'remote-data-blocks-inline-field';
const INLINE_QUERY = 'data-query';
// Core RichText drops an inline format with no content when adjacent text is edited.
export const EMPTY_INLINE_BINDING_PREVIEW = '\u00a0';
const BLOCKED_ATTRIBUTES =
	/^(?:on|data-wp-|autofocus$|contenteditable$|form$|formaction$|is$|ping$|srcdoc$|srcset$|xmlns(?::|$))/;

function allowedURLPattern(): RegExp {
	const protocols = ( window.REMOTE_DATA_BLOCKS_EDITOR_HTML?.protocols ?? [] ).filter(
		protocol =>
			/^[a-z][a-z0-9+.-]*$/i.test( protocol ) &&
			! /^(?:data|javascript|vbscript)$/i.test( protocol )
	);
	const schemes = protocols.map( protocol => protocol.replace( /[.+]/g, '\\$&' ) ).join( '|' );
	return new RegExp( `^(?:[^:/?#]*(?:[/?#]|$)${ schemes ? `|(?:${ schemes }):` : '' })`, 'i' );
}

function isAllowedURL( value: string, pattern = allowedURLPattern() ): boolean {
	return pattern.test( value.replace( /[\p{White_Space}\p{Cc}\uFEFF]/gu, '' ) );
}

function isURIAttribute( name: string ): boolean {
	return (
		name === 'href' ||
		name === 'src' ||
		( window.REMOTE_DATA_BLOCKS_EDITOR_HTML?.uriAttributes.includes( name ) ?? false )
	);
}

function sanitizeStyle( style: string ): string {
	const properties = window.REMOTE_DATA_BLOCKS_EDITOR_HTML?.styleProperties ?? [];
	return style
		.split( ';' )
		.filter( rule => {
			const colon = rule.indexOf( ':' );
			const property = rule.slice( 0, colon ).trim().toLowerCase();
			// Match the safe punctuation used by KSES for declarations and simple CSS functions.
			const value = rule
				.slice( colon + 1 )
				.replace( /\b(?:var|calc|min|max|clamp|rgba?|hsla?)\(/gi, '' );
			return colon > 0 && properties.includes( property ) && ! /[\\(&={}]|\/\*/.test( value );
		} )
		.join( ';' );
}

/** Sanitize editor markup independently of the WordPress version's RichText parser. */
export function sanitizeBindingHTML( html: string ): string {
	const formats = select( richTextStore ).getFormatTypes();
	const policy = window.REMOTE_DATA_BLOCKS_EDITOR_HTML;
	const attributes = new Map( Object.entries( policy?.attributes ?? {} ) );
	const urlPattern = allowedURLPattern();
	DOMPurify.addHook( 'uponSanitizeAttribute', ( node, event ) => {
		const tag = node.nodeName.toLowerCase();
		const name = event.attrName;
		const tagAttributes = attributes.get( tag ) ?? [];
		const isDataAttribute = /^data-[a-z0-9_-]+$/.test( name );
		const isInlineQuery = tag === INLINE_FIELD && name === INLINE_QUERY;
		const formatAttribute = formats.some(
			format =>
				format.tagName === tag &&
				( format.className === null || node.classList.contains( format.className ) ) &&
				( ( name === 'class' && Boolean( format.className ) ) ||
					Object.values( format.attributes ?? {} ).includes( name ) )
		);
		const allowed =
			isInlineQuery ||
			tagAttributes.includes( name ) ||
			( tagAttributes.includes( 'data-*' ) && isDataAttribute ) ||
			formatAttribute;
		// Only the matching format and the inline wrapper can carry opaque data values.
		const requiresURLCheck =
			isURIAttribute( name ) || ( isDataAttribute && ! formatAttribute && ! isInlineQuery );
		if ( ! allowed || BLOCKED_ATTRIBUTES.test( name ) ) {
			event.keepAttr = false;
		} else if ( requiresURLCheck && ! isAllowedURL( event.attrValue, urlPattern ) ) {
			event.keepAttr = false;
		} else if ( name === 'style' ) {
			event.attrValue = sanitizeStyle( event.attrValue );
			event.keepAttr = Boolean( event.attrValue );
		}
		if ( event.keepAttr ) {
			event.allowedAttributes[ name ] = true;
		}
	} );
	try {
		return DOMPurify.sanitize( html, {
			ALLOWED_TAGS: [
				...attributes.keys(),
				INLINE_FIELD,
				...formats
					.filter( format => format.tagName.includes( '-' ) )
					.map( format => format.tagName ),
			],
			FORBID_TAGS: [
				'base',
				'button',
				'datalist',
				'embed',
				'fieldset',
				'form',
				'frame',
				'iframe',
				'input',
				'link',
				'meta',
				'object',
				'option',
				'script',
				'select',
				'style',
				'template',
				'textarea',
			],
			ALLOWED_ATTR: [],
			ALLOW_ARIA_ATTR: false,
			// The attribute hook still controls which tags may carry each data attribute.
			ALLOW_DATA_ATTR: true,
			ALLOWED_URI_REGEXP: urlPattern,
		} );
	} finally {
		DOMPurify.removeHook( 'uponSanitizeAttribute' );
	}
}

export function sanitizeBindingValue(
	value: BindingValue,
	blockName: string | undefined,
	target: string
): BindingValue {
	if ( typeof value !== 'string' ) {
		return value;
	}
	// Preserve the raw block contract if a site explicitly enables this binding in Core.
	if ( blockName === 'remote-data-blocks/remote-html' && target === 'content' ) {
		return value;
	}
	const schema = blockName ? getBlockType( blockName )?.attributes?.[ target ] : undefined;
	if ( schema?.source === 'attribute' && schema.attribute ) {
		return isURIAttribute( schema.attribute.toLowerCase() ) && ! isAllowedURL( value )
			? null
			: value;
	}
	// Core navigation blocks use a URL without an HTML attribute source.
	const isNavigationURL =
		! schema?.source &&
		target === 'url' &&
		( blockName === 'core/navigation-link' || blockName === 'core/navigation-submenu' );
	if ( isNavigationURL && ! isAllowedURL( value ) ) {
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
			if ( ! html.includes( `<${ INLINE_FIELD }` ) ) {
				return [];
			}
			const doc = document.implementation.createHTMLDocument( '' );
			doc.body.innerHTML = html;
			for ( const field of doc.body.querySelectorAll( INLINE_FIELD ) ) {
				field.outerHTML = sanitizeBindingHTML( field.outerHTML );
			}
			for ( const field of doc.body.querySelectorAll( `${ INLINE_FIELD }:empty` ) ) {
				field.textContent = EMPTY_INLINE_BINDING_PREVIEW;
			}
			return [ [ name, doc.body.innerHTML ] ];
		} )
	);
	return Object.keys( changes ).length ? { ...attributes, ...changes } : attributes;
}
