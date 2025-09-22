import { registerBlockBindingsSource } from '@wordpress/blocks';

import { REMOTE_DATA_CONTEXT_KEY } from '@/blocks/remote-data-container/config/constants';
import { BLOCK_BINDING_SOURCE, STORE_NAME as rdbStore } from '@/config/constants';

import type { Selectors } from '@/store';

interface RawRemoteDataContext {
	[ REMOTE_DATA_CONTEXT_KEY ]?: RemoteData;
}

registerBlockBindingsSource< RawRemoteDataContext, RemoteDataBlockBindingArgs >( {
	name: BLOCK_BINDING_SOURCE,
	usesContext: [ REMOTE_DATA_CONTEXT_KEY ],
	getValues( { bindings, context, select } ) {
		if ( ! context[ REMOTE_DATA_CONTEXT_KEY ]?.results?.length ) {
			return {};
		}

		const remoteData = context[ REMOTE_DATA_CONTEXT_KEY ];
		const previewIndex = select< Selectors >( rdbStore ).getPreviewIndex( remoteData.resultId );

		console.log( { previewIndex, resultId: remoteData.resultId } );
		return Object.fromEntries(
			Object.entries( bindings ).map( ( [ targetAttribute, binding ] ) => {
				const index = binding.args.previewIndex ?? previewIndex;
				return [
					targetAttribute,
					remoteData.results?.[ index ?? 0 ]?.result?.[ binding.args.field ]?.value ?? '',
				];
			} )
		);
	},
} );
