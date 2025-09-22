import type {
	BlockEditorStoreActions,
	BlockEditorStoreDescriptor,
	BlockEditorStoreSelectors,
} from '@wordpress/block-editor';
import type { Block, BlockEditProps as BlockEditPropsOriginal } from '@wordpress/blocks';

/**
 * The types provided by @wordpress/blocks are incomplete.
 */

interface GetValuesPayload< Context, Args > {
	bindings: Record< string, { args: Args } >;
	clientId: string;
	context: Context;
	select: < Selectors >( store: StoreDescriptor ) => Selectors;
}

interface SetValuesPayload< Context, Args > extends GetValuesPayload< Context, Args > {
	dispatch: ( store: BlockEditorStoreDescriptor ) => BlockEditorStoreActions;
}

// Properly allow simplified block registration calls when register_block_type() is already called server-side.
// Use a Partial<Block> to allow all attributes to be optional.
// https://github.com/WordPress/gutenberg/issues/53605
type ServerSideBlockConfiguration< T extends Record< string, any > = {} > = Partial<
	Block< T >
> & {
	variations?: Array< BlockVariation< T > >;
};

declare module '@wordpress/blocks' {
	interface BlockEditProps< T extends Record< string, any > > extends BlockEditPropsOriginal< T > {
		name: string;
	}

	interface BlockBindingsSource< Context = Record< string, {} >, Args = {} > {
		canUserEditValue?: (
			payload: Pick< GetValuesPayload< Context, Args >, 'context' | 'select' >
		) => boolean;
		getValues?: ( payload: GetValuesPayload< Context, Args > ) => Values;
		label?: string;
		name: string;
		setValues?: ( payload: SetValuesPayload< Context, Args > ) => void;
		usesContext?: string[];
	}

	function registerBlockBindingsSource< Context, Values >(
		source: BlockBindingsSource< Context, Values >
	): void;

	export function registerBlockType< TAttributes extends Record< string, any > = {} >(
		name: string,
		settings: ServerSideBlockConfiguration< TAttributes >
	): Block< TAttributes > | undefined;
}
