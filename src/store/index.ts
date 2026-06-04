import {
	createReduxStore,
	register,
	type ActionCreator,
	type ReduxStoreConfig,
	type StoreDescriptor,
} from '@wordpress/data';

import { STORE_NAME } from '@/config/constants';

interface State {
	previewIndexes: Map< string, number >;
}

interface SetPreviewIndexAction {
	type: 'SET_PREVIEW_INDEX';
	payload: {
		resultId: string;
		index: number;
	};
}

type ActionPayload = SetPreviewIndexAction;

export interface ActionCreators extends Record< string, ActionCreator > {
	setPreviewIndex: ( resultId: string, index: number ) => SetPreviewIndexAction;
}

export interface Selectors {
	getPreviewIndex: ( resultId?: string ) => number;
}

const actionCreators: ActionCreators = {
	setPreviewIndex( resultId: string, index: number ): SetPreviewIndexAction {
		return {
			type: 'SET_PREVIEW_INDEX',
			payload: { resultId, index },
		};
	},
};

const selectors = {
	getPreviewIndex( state: State = INITIAL_STATE, resultId: string ): number {
		return state.previewIndexes?.get( resultId ) ?? 0;
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
						action.payload.resultId,
						action.payload.index
					),
				};
		}
	},
	selectors,
};

const store = createReduxStore( STORE_NAME, remoteDataBlocksStoreConfig );

( register as ( storeDescriptor: StoreDescriptor ) => void )( store );
