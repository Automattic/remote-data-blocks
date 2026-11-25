import { BlockEditorStoreSelectors, store as blockEditorStore } from '@wordpress/block-editor';
import { registerBlockBindingsSource } from '@wordpress/blocks';

import { REMOTE_DATA_CONTEXT_KEY } from '@/blocks/remote-data-container/config/constants';
import { BLOCK_BINDING_SOURCE, STORE_NAME as rdbStore } from '@/config/constants';
import { resolveRemoteBindingValue } from '@/utils/resolve-remote-binding-value';

import type { Selectors } from '@/store';
import type { BindingValue } from '@/utils/block-binding-value';
import type { StoreDescriptor } from '@wordpress/data';

interface RawRemoteDataContext {
	[ REMOTE_DATA_CONTEXT_KEY ]?: RemoteData;
}

interface GetValuesPayload {
	bindings: Record< string, RemoteDataBlockBinding >;
	clientId: string;
	context: RawRemoteDataContext;
	select: < T >( store: StoreDescriptor | string ) => T;
}

registerBlockBindingsSource( {
	name: BLOCK_BINDING_SOURCE,
	usesContext: [ 'remote-data-blocks/remoteData' ],
	getValues( {
		bindings,
		clientId,
		context,
		select,
	}: GetValuesPayload ): Record< string, BindingValue > {
		const editor = clientId ? select< BlockEditorStoreSelectors >( blockEditorStore ) : undefined;
		const blockName = editor?.getBlockName( clientId );
		const remoteData = context[ REMOTE_DATA_CONTEXT_KEY ];
		const previewIndex = select< Selectors >( rdbStore ).getPreviewIndex( remoteData?.resultId );

		return Object.fromEntries(
			Object.entries( bindings ).flatMap( ( [ target, { args } ] ) => {
				const value = resolveRemoteBindingValue( {
					args,
					blockName,
					target,
					remoteData,
					previewIndex,
					getSavedValue: () => editor?.getBlockAttributes( clientId )?.[ target ],
				} );
				return value === undefined ? [] : [ [ target, value ] ];
			} )
		);
	},
} );
