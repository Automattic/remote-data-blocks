import { BlockEditorStoreSelectors, store as blockEditorStore } from '@wordpress/block-editor';
import { registerBlockBindingsSource } from '@wordpress/blocks';

import { REMOTE_DATA_CONTEXT_KEY } from '@/blocks/remote-data-container/config/constants';
import { BLOCK_BINDING_SOURCE, STORE_NAME as rdbStore } from '@/config/constants';
import {
	coerceBindingValue,
	getBindingTargetType,
	isTextBindingTarget,
} from '@/utils/block-binding-value';
import { sanitizeBindingHTML, sanitizeBindingValue } from '@/utils/sanitize-binding-value';

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
				if (
					typeof args?.field !== 'string' ||
					( ! args.isPreview && ! remoteData?.results?.length )
				) {
					return [];
				}

				const index = args.previewIndex ?? previewIndex ?? 0;
				const rawValue =
					args.previewValue !== undefined
						? args.previewValue
						: remoteData?.results?.[ index ]?.result?.[ args.field ]?.value;
				const targetType = getBindingTargetType( blockName, target );
				const sanitize = ( value: unknown ) =>
					sanitizeBindingValue( coerceBindingValue( value, targetType ), blockName, target );

				let value = sanitize( rawValue );
				if ( value === null && ! args.isPreview ) {
					value = sanitize( editor?.getBlockAttributes( clientId )?.[ target ] );
				}
				if ( value === null && isTextBindingTarget( targetType ) ) {
					value = '';
				}
				if ( typeof value === 'string' && args.label ) {
					value = `${ sanitizeBindingHTML( args.label ) }: ${ value }`;
				}
				return [ [ target, value ] ];
			} )
		);
	},
} );
