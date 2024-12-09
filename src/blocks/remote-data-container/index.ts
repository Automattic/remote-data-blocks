import { BlockInstance, registerBlockBindingsSource, registerBlockType } from '@wordpress/blocks';
import { createReduxStore, register } from '@wordpress/data';
import { ReduxStoreConfig } from '@wordpress/data/build-types/types';
import { addFilter } from '@wordpress/hooks';
import { registerFormatType } from '@wordpress/rich-text';

import { fetchRemoteData } from './hooks/useRemoteData';
import { formatTypeSettings } from '@/blocks/remote-data-container/components/field-shortcode';
import { FieldShortcodeButton } from '@/blocks/remote-data-container/components/field-shortcode/FieldShortcodeButton';
import { Edit } from '@/blocks/remote-data-container/edit';
import { addUsesContext } from '@/blocks/remote-data-container/filters/addUsesContext';
import { withBlockBindingShim } from '@/blocks/remote-data-container/filters/withBlockBinding';
import { Save } from '@/blocks/remote-data-container/save';
import { getBlocksConfig } from '@/utils/localized-block-data';
import './style.scss';

// Register a unique block definition for each of the context blocks.
Object.values( getBlocksConfig() ).forEach( blockConfig => {
	registerBlockType< RemoteDataBlockAttributes >( blockConfig.name, {
		...blockConfig.settings,
		attributes: {
			remoteData: {
				type: 'object',
			},
		},
		edit: Edit,
		save: Save,
	} );
} );

// Register the field shortcode format type.
registerFormatType( 'remote-data-blocks/field-shortcode', {
	...formatTypeSettings,
	edit: FieldShortcodeButton,
} );

/**
 * Use a filter to wrap the block edit component with our block binding HOC.
 * We are intentionally using the `blocks.registerBlockType` filter instead of
 * `editor.BlockEdit` so that we can make sure our HOC is applied after any
 * other HOCs from Core -- specifically this one, which injects the binding label
 * as the attribute value:
 *
 * https://github.com/WordPress/gutenberg/blob/f56dbeb9257c19acf6fbd8b45d87ae8a841624da/packages/block-editor/src/hooks/use-bindings-attributes.js#L159
 */
addFilter(
	'blocks.registerBlockType',
	'remote-data-blocks/withBlockBinding',
	withBlockBindingShim,
	5 // Ensure this runs before core filters
);

/**
 * Use a filter to inject usesContext to core block settings.
 */
addFilter( 'blocks.registerBlockType', 'remote-data-blocks/addUsesContext', addUsesContext, 10 );

interface State {}

type Actions = {
	GET_DATA: () => void;
};

interface Selectors {}

const remoteDataBlocksStoreConfig: ReduxStoreConfig< State, Actions, Selectors > = {
	reducer: ( state = {}, action ) => {
		switch ( action.type ) {
			case 'RECEIVE_REMOTE_DATA':
				return { ...state, [ action.queryKey ]: action.data };
		}
		return state;
	},
	selectors: {
		getRemoteData: ( state, queryKey, blockName, queryInput = {} ) => {
			return state[ `${ queryKey }:${ JSON.stringify( queryInput ) }` ];
		},
	},
	resolvers: {
		getRemoteData:
			( queryKey: string, blockName: string, queryInput: Record< string, string > ) =>
			async ( { dispatch } ) => {
				try {
					const data = await fetchRemoteData( {
						block_name: blockName,
						query_key: queryKey,
						query_input: queryInput,
					} );
					dispatch( { type: 'RECEIVE_REMOTE_DATA', queryKey, data } );
				} catch ( err: unknown ) {
					dispatch( { type: 'RECEIVE_REMOTE_DATA_ERROR', queryKey, error: err } );
				}
			},
	},
};

const remoteDataBlocksStore = createReduxStore(
	'remote-data-blocks-store',
	remoteDataBlocksStoreConfig
);

register( remoteDataBlocksStore );

registerBlockBindingsSource( {
	name: 'remote-data/binding',
	label: 'Remote Data Binding',
	usesContext: [ 'remote-data-blocks/remoteData' ],
	getValues( { context, clientId, bindings, select } ) {
		console.log( { context, clientId, bindings } );
		const remoteDataContext = context[ 'remote-data-blocks/remoteData' ];

		if ( remoteDataContext === undefined ) {
			return null;
		}

		const input = remoteDataContext.queryInput;

		const blockConfig: BlockInstance< {} > =
			select( 'core/block-editor' ).getBlocksByClientId( clientId )[ 0 ];
		const blockName = blockConfig.name;

		console.log( { blockConfig } );

		const data = select( remoteDataBlocksStore ).getRemoteData(
			blockName,
			'queryKey',
			remoteDataContext
		);
		console.log( { data } );

		const newValues = {};

		for ( const [ attributeName, source ] of Object.entries( bindings ) ) {
			const { key, field } = source.args;
			// const { gravatar_id: id } =
			// 	getEditedEntityRecord( 'postType', context?.postType, context?.postId ).meta || {};
			// const data = select( gravatarStore ).getGravatarData( id );
			newValues[ attributeName ] = 'TEST'; // data?.[ key || field ];
		}
		return newValues;
	},
} );
