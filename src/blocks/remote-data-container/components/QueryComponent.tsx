import {
	BlockEditorStoreSelectors,
	InnerBlocks,
	InspectorControls,
	useBlockProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { Spinner } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';

import { EditErrorBoundary } from './EditErrorBoundary';
import { DataPanel } from './panels/DataPanel';
import { OverridesPanel } from './panels/OverridesPanel';
import { QueryInputsPanel } from './panels/QueryInputsPanel';
import { PatternSelection } from '@/blocks/remote-data-container/components/pattern-selection/PatternSelection';
import { CONTAINER_CLASS_NAME } from '@/blocks/remote-data-container/config/constants';
import { usePatterns } from '@/blocks/remote-data-container/hooks/usePatterns';
import { useRemoteData } from '@/blocks/remote-data-container/hooks/useRemoteData';
import { hasRemoteDataChanged } from '@/utils/block-binding';
import { getBlockTitle, getSelectorsForDisplayQuery } from '@/utils/localized-block-data';

export interface QueryComponentProps {
	displayQueryKey: string;
	blockConfig: BlockConfig;
	blockName: string;
	rootClientId: string;
	remoteDataAttribute: RemoteData | undefined;
	setAttributes: ( attributes: RemoteDataBlockAttributes ) => void;
	queryInputs: RemoteDataQueryInput[];
	onQueryInputsChange?: ( inputs: RemoteDataQueryInput[] ) => void;
	resetQuery: () => void;
}

export function RemoteDataBlockComponent( props: QueryComponentProps ) {
	const {
		displayQueryKey,
		blockConfig,
		blockName,
		rootClientId,
		remoteDataAttribute,
		setAttributes,
		queryInputs,
		resetQuery,
	} = props;

	const { data, fetch, reset, supportsPagination, loading } = useRemoteData( {
		blockName,
		externallyManagedRemoteData: remoteDataAttribute,
		externallyManagedUpdateRemoteData: updateRemoteData,
		// This is done on purpose, as the selector query is the input to the display query.
		// So the real query being executed is the display query.
		displayQueryKey,
		selectorQueryKey: displayQueryKey,
	} );

	const {
		getSupportedPatterns,
		onSelectPattern,
		onReadyForPatternSelection,
		resetPatternSelection,
		showPatternSelection,
	} = usePatterns( blockName, rootClientId, displayQueryKey, supportsPagination );

	const { hasMultiSelection } = useSelect< BlockEditorStoreSelectors >( blockEditorStore );

	useEffect( () => {
		onSelectRemoteData( queryInputs );
	}, [ queryInputs ] );

	function onSelectRemoteData( inputs: RemoteDataQueryInput[] ): void {
		// if the old queryInputs and new ones are the same, skip this call.
		if ( JSON.stringify( remoteDataAttribute?.queryInputs ) === JSON.stringify( inputs ) ) {
			return;
		}

		void fetch( inputs ).then( () => {
			onReadyForPatternSelection();
		} );
	}

	function refreshRemoteData(): void {
		void fetch( remoteDataAttribute?.queryInputs ?? [ {} ] );
	}

	function resetRemoteData(): void {
		reset();
		resetPatternSelection();
		resetQuery();
	}

	function updateRemoteData( remoteData?: RemoteData ): void {
		if ( hasRemoteDataChanged( remoteDataAttribute, remoteData ) ) {
			setAttributes( { remoteData } );
		}
	}

	function onUpdateQueryInputs(
		newSelectorQueryKey: string,
		inputs: RemoteDataQueryInput[]
	): void {
		if ( ! remoteDataAttribute ) {
			return;
		}

		updateRemoteData( {
			...remoteDataAttribute,
			queryInputs: inputs,
			// This will always be the display query key.
			selectorQueryKey: newSelectorQueryKey,
			displayQueryKey,
		} );
		refreshRemoteData();
	}

	function renderLoadingOverlay( isClickable = false ): JSX.Element {
		return (
			<div
				className="remote-data-blocks-loading-overlay"
				style={ isClickable ? { pointerEvents: 'auto' } : undefined }
			>
				<Spinner
					style={ {
						height: '50px',
						width: '50px',
					} }
				/>
			</div>
		);
	}

	if ( showPatternSelection ) {
		const supportedPatterns = getSupportedPatterns( data?.results[ 0 ] );

		return (
			<PatternSelection
				blockName={ blockName }
				onCancel={ resetPatternSelection }
				onSelectPattern={ onSelectPattern }
				supportedPatterns={ supportedPatterns }
			/>
		);
	}

	return (
		<>
			{ ! hasMultiSelection() && data && (
				<InspectorControls>
					<OverridesPanel
						blockConfig={ blockConfig }
						remoteData={ data }
						updateRemoteData={ updateRemoteData }
					/>
					<DataPanel
						refreshRemoteData={ refreshRemoteData }
						remoteData={ data }
						resetRemoteData={ resetRemoteData }
					/>
					<QueryInputsPanel
						onUpdateQueryInputs={ onUpdateQueryInputs }
						remoteData={ data }
						selectors={ getSelectorsForDisplayQuery( blockName, displayQueryKey ) }
					/>
				</InspectorControls>
			) }
			{ loading && renderLoadingOverlay() }
			<InnerBlocks />
		</>
	);
}

export function QueryComponent( props: QueryComponentProps ) {
	const blockProps = useBlockProps( { className: CONTAINER_CLASS_NAME } );

	return (
		<>
			<div { ...blockProps }>
				<EditErrorBoundary blockTitle={ getBlockTitle( props.blockName ) }>
					<RemoteDataBlockComponent { ...props } />
				</EditErrorBoundary>
			</div>
		</>
	);
}
