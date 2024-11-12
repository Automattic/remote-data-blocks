import { BlockEditProps as BlockEditPropsOriginal } from '@wordpress/blocks';

/**
 * The types provided by @wordpress/blocks are incomplete.
 */

declare module '@wordpress/blocks' {
	interface BlockEditProps< T extends Record< string, any > > extends BlockEditPropsOriginal< T > {
		name: string;
	}

	function registerBlockBindingsSource(source: {
		name: string;
		label?: string;
		usesContext?: string[];
		getValues?: (context: any) => any;
		setValues?: (context: any, values: any) => void;
		canUserEditValue?: (context: any) => boolean;
	}): void;
}
