import { BlockEditorStoreSelectors, store as blockEditorStore } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';

/** WordPress 6.7's supported list. Never merge it into the modern filtered map. */
const LEGACY_SUPPORTED_ATTRIBUTES: Record< string, string[] > = {
	'core/paragraph': [ 'content' ],
	'core/heading': [ 'content' ],
	'core/image': [ 'id', 'url', 'title', 'alt' ],
	'core/button': [ 'url', 'text', 'linkTarget', 'rel' ],
};

interface BindingEditorSettings {
	__experimentalBlockBindingsSupportedAttributes?: unknown;
}

export function getSupportedBindingAttributes(
	settings: BindingEditorSettings,
	blockName: string
): string[] {
	// Remote HTML has its own sandboxed rendering path, independent of core support.
	if ( blockName === 'remote-data-blocks/remote-html' ) {
		return [ 'content' ];
	}
	const map = settings.__experimentalBlockBindingsSupportedAttributes;
	if ( map === undefined || map === null ) {
		return LEGACY_SUPPORTED_ATTRIBUTES[ blockName ] ?? [];
	}
	// PHP encodes an empty associative map as []; it must not re-enable legacy attributes.
	if ( typeof map !== 'object' || Array.isArray( map ) ) {
		return [];
	}
	const attributes: unknown = ( map as Record< string, unknown > )[ blockName ];
	return Array.isArray( attributes )
		? [
				...new Set(
					attributes.filter(
						( name ): name is string => typeof name === 'string' && name.trim().length > 0
					)
				),
		  ]
		: [];
}

/** Subscribe at render time: registration may run before editor settings exist. */
export function useSupportedBindingAttributes( blockName: string ): string[] {
	return useSelect(
		select => {
			const { getSettings } = select( blockEditorStore ) as BlockEditorStoreSelectors;
			return getSupportedBindingAttributes( getSettings() as BindingEditorSettings, blockName );
		},
		[ blockName ]
	);
}
