// @ts-expect-error Temporary registerBlockBindingsSource type error workaround for WordPress 6.7
import { registerBlockBindingsSource as originalRegisterBlockBindingsSource } from '@wordpress/blocks';

import type {
	BlockEditorStoreActions,
	BlockEditorStoreSelectors,
	BlockEditorStoreDescriptor,
} from '@wordpress/block-editor';

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

export interface BlockBindingsSource< Context = Record< string, unknown >, Values = unknown > {
	canUserEditValue?: ( payload: GetValuesPayload< Context, Values > ) => boolean;
	getValues?: ( payload: GetValuesPayload< Context, Values > ) => Values;
	label?: string;
	name: string;
	setValues?: ( payload: SetValuesPayload< Context, Values > ) => void;
	usesContext?: string[];
}

export function registerBlockBindingsSource< Context, Values >(
	source: BlockBindingsSource< Context, Values >
): void {
	// eslint-disable-next-line @typescript-eslint/no-unsafe-call
	originalRegisterBlockBindingsSource( source );
}
