import { registerBlockBindingsSource } from '@wordpress/blocks';

import { REMOTE_DATA_CONTEXT_KEY } from '@/blocks/remote-data-container/config/constants';
import { BLOCK_BINDING_SOURCE, STORE_NAME as rdbStore } from '@/config/constants';

import type { Selectors } from '@/store';

interface RawRemoteDataContext {
	[ REMOTE_DATA_CONTEXT_KEY ]?: RemoteData;
}

registerBlockBindingsSource< RawRemoteDataContext, RemoteDataBlockBinding >( {
	name: BLOCK_BINDING_SOURCE,
	usesContext: [ 'remote-data-blocks/remoteData' ],
	getValues( { bindings, context, select } ): Record< string, string > {
		if ( ! context[ REMOTE_DATA_CONTEXT_KEY ]?.results?.length ) {
			return {};
		}

		const remoteData = context[ REMOTE_DATA_CONTEXT_KEY ];
		const previewIndex = select< Selectors >( rdbStore ).getPreviewIndex( remoteData.resultId );

		return Object.fromEntries(
			Object.entries( bindings ).map( ( [ targetAttribute, binding ] ): [ string, string ] => {
				const index = binding.args.previewIndex ?? previewIndex;
				const label = binding.args.label ? `${ binding.args.label }: ` : '';
				const value = String(
					remoteData.results?.[ index ?? 0 ]?.result?.[ binding.args.field ]?.value ?? ''
				);

				return [ targetAttribute, `${ label }${ value }` ];
			} )
		);
	},
} );
