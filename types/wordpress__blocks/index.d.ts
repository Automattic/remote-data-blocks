import type {
	BlockEditorStoreActions,
	BlockEditorStoreDescriptor,
	BlockEditorStoreSelectors,
} from '@wordpress/block-editor';
import type { StoreDescriptor } from '@wordpress/data';
import type { Block, BlockType } from '@wordpress/blocks';

/**
 * The types provided by @wordpress/blocks are incomplete.
 */

interface ContextSelectPayload< Context > {
	context: Context;
	select: < Selectors >( store: StoreDescriptor ) => Selectors;
}

interface GetValuesPayload< Context, Binding > extends ContextSelectPayload< Context > {
	bindings: Record< string, Binding >;
	clientId: string;
}

interface SetValuesPayload< Context, Binding > extends GetValuesPayload< Context, Binding > {
	dispatch: ( store: BlockEditorStoreDescriptor ) => BlockEditorStoreActions;
}

interface BaseBinding {
	args: object;
	source: string;
}

// Properly allow simplified block registration calls when register_block_type() is already called server-side.
// Use a Partial<Block> to allow all attributes to be optional.
// https://github.com/WordPress/gutenberg/issues/53605
type ServerSideBlockConfiguration< T extends Record< string, any > = {} > = Partial<
	Omit< BlockType< T >, 'icon' | 'variations' >
> & {
	icon?: any;
	variations?: Array< any >;
};

declare module '@wordpress/blocks' {
	export type BlockAttributes = Record< string, unknown >;
	export type BlockInstance< T extends Record< string, unknown > = Record< string, unknown > > =
		Block< T >;
	export type Template = [ string, Record< string, unknown >?, Template[]? ];

	interface BlockEditProps< T extends Record< string, any > = Record< string, any > > {
		name: string;
	}

	interface BlockBindingsSource<
		Context = Record< string, unknown >,
		Binding extends BaseBinding,
		Values extends Record< string, unknown >,
	> {
		canUserEditValue?: ( payload: ContextSelectPayload< Context > ) => boolean;
		getValues?: ( payload: GetValuesPayload< Context, Binding > ) => Values;
		label?: string;
		name: string;
		setValues?: ( payload: SetValuesPayload< Context, Binding > ) => void;
		usesContext?: string[];
	}

	function registerBlockBindingsSource<
		Context,
		Binding extends BaseBinding,
		Values = Record< string, unknown >,
	>( source: BlockBindingsSource< Context, Binding, Values > ): void;

	export function registerBlockType< TAttributes extends Record< string, any > = {} >(
		name: string,
		settings: ServerSideBlockConfiguration< TAttributes >
	): Block< TAttributes > | undefined;
}
