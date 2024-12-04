import type { BlockEditProps as BlockEditPropsOriginal } from '@wordpress/blocks';
import type {
	BlockEditorStoreActions,
	BlockEditorStoreSelectors,
	BlockEditorStoreDescriptor,
} from '@wordpress/block-editor';

/**
 * The types provided by @wordpress/blocks are incomplete.
 */

interface GetValuesPayload< Context, Values > {
	bindings: Values;
	clientId: string;
	context: Context;
	select: ( store: BlockEditorStoreDescriptor ) => BlockEditorStoreSelectors;
}

interface SetValuesPayload< Context, Values > extends GetValuesPayload< Context, Values > {
	dispatch: ( store: BlockEditorStoreDescriptor ) => BlockEditorStoreActions;
	values: Values;
}

declare module '@wordpress/blocks' {
	interface BlockEditProps< T extends Record< string, any > > extends BlockEditPropsOriginal< T > {
		name: string;
	}

	interface BlockBindingsSource< Context = Record< string, unknown >, Values = unknown > {
		canUserEditValue?: ( payload: GetValuesPayload< Context, Values > ) => boolean;
		getValues?: ( payload: GetValuesPayload< Context, Values > ) => Values;
		label?: string;
		name: string;
		setValues?: ( payload: SetValuesPayload< Context, Values > ) => void;
		usesContext?: string[];
	}

	function registerBlockBindingsSource< Context, Values >(
		source: BlockBindingsSource< Context, Values >
	): void;
}
