import { BlockEditorStoreSelectors, store as blockEditorStore } from '@wordpress/block-editor';
import { registerBlockBindingsSource } from '@wordpress/blocks';

import { REMOTE_DATA_CONTEXT_KEY } from '@/blocks/remote-data-container/config/constants';
import { BLOCK_BINDING_SOURCE, STORE_NAME as rdbStore } from '@/config/constants';
import {
	BindingValue,
	coerceBindingValue,
	getBindingTargetType,
	isTextBindingTarget,
} from '@/utils/block-binding-value';

import type { Selectors } from '@/store';
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
			Object.entries( bindings )
				.filter( ( [ _targetAttribute, binding ] ): boolean => {
					return binding.args.isPreview || Boolean( remoteData?.results?.length );
				} )
				.map( ( [ targetAttribute, binding ] ): [ string, BindingValue ] => {
					const index = binding.args.previewIndex ?? previewIndex ?? 0;
					const label = binding.args.label ? `${ binding.args.label }: ` : '';
					const rawValue =
						binding.args.previewValue !== undefined
							? binding.args.previewValue
							: remoteData?.results?.[ index ]?.result?.[ binding.args.field ]?.value;
					const targetType = getBindingTargetType( blockName, targetAttribute );
					let value = coerceBindingValue( rawValue, targetType );
					if ( value === null && ! binding.args.isPreview ) {
						value = coerceBindingValue(
							editor?.getBlockAttributes( clientId )?.[ targetAttribute ],
							targetType
						);
					}
					// Text targets keep their legacy contract of always receiving a string.
					if ( value === null && isTextBindingTarget( targetType ) ) {
						value = '';
					}
					return [ targetAttribute, typeof value === 'string' ? `${ label }${ value }` : value ];
				} )
		);
	},
} );
