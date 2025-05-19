import {
	BlockEditorStoreSelectors,
	BlockPattern,
	InnerBlocks,
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
import { Spinner } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';

import { DataPanel } from './panels/DataPanel';
import { OverridesPanel } from './panels/OverridesPanel';
import { QueryInputsPanel } from './panels/QueryInputsPanel';
import { PatternSelection } from '@/blocks/remote-data-container/components/pattern-selection/PatternSelection';
import { CONTAINER_CLASS_NAME } from '@/blocks/remote-data-container/config/constants';
import { usePatterns } from '@/blocks/remote-data-container/hooks/usePatterns';
import { useRemoteData } from '@/blocks/remote-data-container/hooks/useRemoteData';
import { hasRemoteDataChanged } from '@/utils/block-binding';

export interface QueryComponentProps {
	queryGroup: string;
	blockConfig: BlockConfig;
	blockName: string;
	rootClientId: string;
	remoteDataAttribute: RemoteData | undefined;
	setAttributes: ( attributes: RemoteDataBlockAttributes ) => void;
	queryInputs: RemoteDataQueryInput[];
	onQueryInputsChange?: ( inputs: RemoteDataQueryInput[] ) => void;
	resetQuery: () => void;
}

export function QueryComponent( props: QueryComponentProps ) {
	const {
		queryGroup,
		blockConfig,
		blockName,
		rootClientId,
		remoteDataAttribute,
		setAttributes,
		queryInputs,
		resetQuery,
	} = props;

	const blockProps = useBlockProps( { className: CONTAINER_CLASS_NAME } );
	const { getSupportedPatterns, innerBlocksPattern, insertPatternBlocks, resetInnerBlocks } =
		usePatterns( blockName, rootClientId );
	const { data, fetch, reset, supportsPagination, loading } = useRemoteData( {
		blockName,
		externallyManagedRemoteData: remoteDataAttribute,
		externallyManagedUpdateRemoteData: updateRemoteData,
		// This is done on purpose as we want to execute the query with the same group as the query key, aka the display query.
		queryGroup,
		queryKey: queryGroup,
	} );

	// ToDo: Fix this.
	// const { hasMultiSelection } = useSelect< BlockEditorStoreSelectors >( blockEditorStore );
	const [ showPatternSelection, setShowPatternSelection ] = useState< boolean >( false );

	useEffect( () => {
		onSelectRemoteData( queryInputs );
	}, [ queryInputs ] );

	function onSelectRemoteData( inputs: RemoteDataQueryInput[] ): void {
		// if the old queryInputs and new ones are the same, skip this call.
		if ( JSON.stringify( remoteDataAttribute?.queryInputs ) === JSON.stringify( inputs ) ) {
			return;
		}

		void fetch( inputs ).then( () => {
			if ( innerBlocksPattern ) {
				insertPatternBlocks( innerBlocksPattern, supportsPagination );
				return;
			}

			setShowPatternSelection( true );
		} );
	}

	function refreshRemoteData(): void {
		void fetch( remoteDataAttribute?.queryInputs ?? [ {} ] );
	}

	function resetPatternSelection(): void {
		resetInnerBlocks();
		setShowPatternSelection( false );
	}

	function resetRemoteData(): void {
		reset();
		resetPatternSelection();
		resetQuery();
	}

	function onSelectPattern( pattern: BlockPattern ): void {
		insertPatternBlocks( pattern, supportsPagination );
		setShowPatternSelection( false );
	}

	function updateRemoteData( remoteData?: RemoteData ): void {
		if ( hasRemoteDataChanged( remoteDataAttribute, remoteData ) ) {
			setAttributes( { remoteData } );
		}
	}

	function onUpdateQueryInputs( newQueryKey: string, inputs: RemoteDataQueryInput[] ): void {
		if ( ! remoteDataAttribute ) {
			return;
		}

		updateRemoteData( {
			...remoteDataAttribute,
			queryInputs: inputs,
			queryKey: newQueryKey,
		} );
		refreshRemoteData();
	}

	if ( showPatternSelection ) {
		const supportedPatterns = getSupportedPatterns( data?.results[ 0 ] );

		return (
			<div { ...blockProps }>
				<PatternSelection
					blockName={ blockName }
					onCancel={ resetPatternSelection }
					onSelectPattern={ onSelectPattern }
					supportedPatterns={ supportedPatterns }
				/>
			</div>
		);
	}

	return (
		<>
			{ data && (
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
						selectors={ blockConfig.selectors.filter(
							selectors => selectors.query_group === queryGroup
						) }
					/>
				</InspectorControls>
			) }
			<div { ...blockProps }>
				{ loading && (
					<div className="remote-data-blocks-loading-overlay">
						<Spinner
							style={ {
								height: '50px',
								width: '50px',
							} }
						/>
					</div>
				) }
				<InnerBlocks />
			</div>
		</>
	);
}
