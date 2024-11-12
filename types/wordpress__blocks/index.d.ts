import { BlockEditProps as BlockEditPropsOriginal } from '@wordpress/blocks';

/**
 * The types provided by @wordpress/blocks are incomplete.
 */

declare module '@wordpress/blocks' {
	function alecgBlocksTest(): void;

	interface BlockEditProps< T extends Record< string, any > > extends BlockEditPropsOriginal< T > {
		name: string;
	}
}
