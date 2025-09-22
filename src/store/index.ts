import { createReduxStore, register, type StoreDescriptor } from '@wordpress/data';

import { STORE_NAME } from '@/config/constants';

import type { ActionCreator, ReduxStoreConfig } from '@wordpress/data/build-types/types';

interface State {
	previewIndexes: Map< string, number >;
}

interface SetPreviewIndexAction {
	type: 'SET_PREVIEW_INDEX';
	payload: {
		resultsId: string;
		index: number;
	};
}

type ActionPayload = SetPreviewIndexAction;

export interface ActionCreators extends Record< string, ActionCreator > {
	setPreviewIndex: ( resultsId: string, index: number ) => SetPreviewIndexAction;
}

export interface Selectors {
	getPreviewIndex: ( resultsId: string ) => number;
}

const actionCreators: ActionCreators = {
	setPreviewIndex( resultsId: string, index: number ): SetPreviewIndexAction {
		return {
			type: 'SET_PREVIEW_INDEX',
			payload: { resultsId, index },
		};
	},
};

const selectors = {
	getPreviewIndex( state: State = INITIAL_STATE, resultsId: string ): number {
		return state.previewIndexes?.get( resultsId ) ?? 0;
	},
};

const INITIAL_STATE: State = {
	previewIndexes: new Map(),
};

const remoteDataBlocksStoreConfig: ReduxStoreConfig< State, ActionCreators, typeof selectors > = {
	actions: actionCreators,
	reducer: ( state: State = INITIAL_STATE, action: ActionPayload ) => {
		switch ( action.type ) {
			case 'SET_PREVIEW_INDEX':
				return {
					...state,
					previewIndexes: new Map( state.previewIndexes ).set(
						action.payload.resultsId,
						action.payload.index
					),
				};
		}
	},
	selectors,
};

const store = createReduxStore( STORE_NAME, remoteDataBlocksStoreConfig );

( register as ( storeDescriptor: StoreDescriptor ) => void )( store );
