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
		const remoteData = context[ REMOTE_DATA_CONTEXT_KEY ];
		const previewIndex = select< Selectors >( rdbStore ).getPreviewIndex( remoteData?.resultId );

		return Object.fromEntries(
			Object.entries( bindings )
				.filter( ( [ _targetAttribute, binding ] ): boolean => {
					return binding.args.isPreview || Boolean( remoteData?.results?.length );
				} )
				.map( ( [ targetAttribute, binding ] ): [ string, string ] => {
					const index = binding.args.previewIndex ?? previewIndex ?? 0;
					const label = binding.args.label ? `${ binding.args.label }: ` : '';
					const value =
						binding.args.previewValue ??
						String( remoteData?.results?.[ index ]?.result?.[ binding.args.field ]?.value ?? '' );

					return [ targetAttribute, `${ label }${ value }` ];
				} )
		);
	},
} );
