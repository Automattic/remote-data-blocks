import { BlockEditorStoreSelectors } from '@wordpress/block-editor';
import { BlockBindingsSource, registerBlockBindingsSource } from '@wordpress/blocks';

import { BlockBindingControls } from '@/blocks/remote-data-container/components/BlockBindingControls';
import { REMOTE_DATA_CONTEXT_KEY } from '@/blocks/remote-data-container/config/constants';
import { BLOCK_BINDING_SOURCE, STORE_NAME as rdbStore } from '@/config/constants';
import { getBlockAvailableBindings } from '@/utils/localized-block-data';

import type { Selectors } from '@/store';

interface RemoteDataRawContext {
	[ REMOTE_DATA_CONTEXT_KEY ]?: RemoteData;
}

interface EditorUIDatum {
	field: string;
	label: string;
	type: string;
	value?: string;
}

type EditorUIFn = BlockBindingsSource< RemoteDataRawContext, RemoteDataBlockBinding >[ 'editorUI' ];

// eslint-disable-next-line @typescript-eslint/no-unused-vars
const dropDownEditorUI: EditorUIFn = ( { context } ) => {
	const remoteData = context[ REMOTE_DATA_CONTEXT_KEY ];
	const availableBindings = getBlockAvailableBindings( remoteData?.blockName ?? '' );
	const hasAvailableBindings = Boolean( Object.keys( availableBindings ).length );

	if ( ! remoteData || ! hasAvailableBindings ) {
		return {};
	}

	return {
		mode: 'dropdown',
		data: Object.entries( availableBindings ).map(
			( [ field, binding ] ): EditorUIDatum => ( {
				field,
				label: binding.name,
				type: 'string',
				value: remoteData.results?.[ 0 ]?.result?.[ field ]?.value as string | undefined,
			} )
		),
		getArgs( { item }: { item: EditorUIDatum } ) {
			return {
				block: remoteData.blockName,
				field: item.field,
			};
		},
		isSelected( {
			item,
			binding,
		}: {
			item: EditorUIDatum;
			binding: RemoteDataBlockBinding;
		} ): boolean {
			return binding?.args?.field === item.field;
		},
	};
};

const modalEditorUI: EditorUIFn = ( { context, select } ) => {
	const remoteData = context[ REMOTE_DATA_CONTEXT_KEY ];
	const availableBindings = getBlockAvailableBindings( remoteData?.blockName ?? '' );
	const hasAvailableBindings = Boolean( Object.keys( availableBindings ).length );
	const block =
		select< BlockEditorStoreSelectors >(
			'core/block-editor'
		)?.getSelectedBlock< RemoteDataInnerBlockAttributes >();

	if ( ! remoteData || ! hasAvailableBindings || ! block ) {
		return {};
	}

	return {
		mode: 'modal',
		data: Object.entries( availableBindings ).map( ( [ key, binding ] ) => ( {
			key,
			label: binding.name,
			type: binding.type,
			value: key,
		} ) ),
		isSelected( {
			item,
			binding,
		}: {
			item: EditorUIDatum;
			binding: RemoteDataBlockBinding;
		} ): boolean {
			return binding?.args?.field === item.field;
		},
		renderModalContent( { attribute } ) {
			return (
				<BlockBindingControls
					args={ block?.attributes?.metadata?.bindings?.[ attribute ]?.args }
					availableBindings={ availableBindings }
					blockName={ block?.name }
					remoteDataName={ remoteData.blockName }
				/>
			);
		},
	};
};

registerBlockBindingsSource< RemoteDataRawContext, RemoteDataBlockBinding >( {
	name: BLOCK_BINDING_SOURCE,
	usesContext: [ 'remote-data-blocks/remoteData' ],
	// editorUI: dropDownEditorUI,
	editorUI: modalEditorUI,
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
