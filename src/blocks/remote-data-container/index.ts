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
import * as constants from './config/constants';

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

const queryDataStateKey = (queryKey: string, blockName: string, queryInput: Record<string, string>) => `${blockName}:${queryKey}:${JSON.stringify(queryInput)}`;

const remoteDataBlocksStoreConfig: ReduxStoreConfig< State, Actions, Selectors > = {
	reducer: ( state = {}, action ) => {
		switch ( action.type ) {
			case 'RECEIVE_REMOTE_DATA':
				console.log('store: RECEIVED REMOTE DATA')
				console.log({action});
				console.log(`key: ${queryDataStateKey(action.queryKey, action.blockName, action.queryInput)}`);
				return { ...state, [ queryDataStateKey(action.queryKey, action.blockName, action.queryInput) ]: action.data };
			case 'RECEIVE_REMOTE_DATA_ERROR':
				console.log('store: RECEIVED REMOTE DATA ERROR')
				return { ...state, [ queryDataStateKey(action.queryKey, action.blockName, action.queryInput) ]: action.error };
		}
		return state;
	},
	selectors: {
		getRemoteData: ( state, queryKey, blockName, queryInput = {} ) => {
			// console.log( 'store: CALLED SELECTOR' );
			console.log( { state, queryKey, blockName, queryInput } );
			return state[ queryDataStateKey(queryKey, blockName, queryInput) ];
		},
	},
	resolvers: {
		getRemoteData:
			( queryKey: string, blockName: string, queryInput: Record< string, string > ) =>
			async ( { dispatch } ) => {
				// console.log( 'store: CALLED RESOLVER', { queryKey, blockName, queryInput } );
				try {
					console.log({blockName, queryKey, queryInput });
					const data = await fetchRemoteData( {
						block_name: blockName,
						query_key: queryKey,
						query_input: queryInput,
					} );
					// console.log('store: DISPATCHING RECEIVE_REMOTE_DATA')
					console.log( { retrievedData: data } );
					dispatch( { type: 'RECEIVE_REMOTE_DATA', blockName, queryKey, data, queryInput } );
				} catch ( err: unknown ) {
					console.error(err);
					dispatch( { type: 'RECEIVE_REMOTE_DATA_ERROR', blockName, queryKey, error: err } );
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
	getValues( { context, clientId, bindings, select, ...other } ) {
		console.log({other});
		const remoteDataContext = context[ 'remote-data-blocks/remoteData' ];
		console.log({CONTEXT:remoteDataContext});

		if ( remoteDataContext === undefined ) {
			const nullValues = {};
			for ( const [ attributeName, source ] of Object.entries( bindings ) ) {
				const { key, field } = source.args;
				// const { gravatar_id: id } =
				// 	getEditedEntityRecord( 'postType', context?.postType, context?.postId ).meta || {};
				// const data = select( gravatarStore ).getGravatarData( id );
				nullValues[ attributeName ] = '123'; // data?.[ key || field ];
			}
			// console.log( remoteDataContext?.results?.[ 0 ] );
			return nullValues;
		}

		const data = select( remoteDataBlocksStore ).getRemoteData(
			constants.DISPLAY_QUERY_KEY,
			remoteDataContext.blockName,
			remoteDataContext.queryInput
		);

		const result = data?.results?.[0];

		const newValues = {};

		for ( const [ attributeName, source ] of Object.entries( bindings ) ) {
			const { key, field } = source.args;
			// const { gravatar_id: id } =
			// 	getEditedEntityRecord( 'postType', context?.postType, context?.postId ).meta || {};
			// const data = select( gravatarStore ).getGravatarData( id );
			newValues[ attributeName ] = result?.[ field ]?.toString() ?? 'TEST'; // data?.[ key || field ];
		}
		// console.log( remoteDataContext?.results?.[ 0 ] );
		return newValues;
	},
} );
