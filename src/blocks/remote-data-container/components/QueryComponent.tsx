import {
	BlockPattern,
	InnerBlocks,
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
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
	queryKeySelected: string;
	blockConfig: BlockConfig;
	blockName: string;
	rootClientId: string;
	remoteDataAttribute: RemoteData | undefined;
	setAttributes: ( attributes: RemoteDataBlockAttributes ) => void;
	queryInputs: RemoteDataQueryInput[];
	onQueryInputsChange?: ( inputs: RemoteDataQueryInput[] ) => void;
}

export function QueryComponent( props: QueryComponentProps ) {
	const {
		queryKeySelected,
		blockConfig,
		blockName,
		rootClientId,
		remoteDataAttribute,
		setAttributes,
		queryInputs,
		onQueryInputsChange,
	} = props;

	const blockProps = useBlockProps( { className: CONTAINER_CLASS_NAME } );
	const { getSupportedPatterns, innerBlocksPattern, insertPatternBlocks, resetInnerBlocks } =
		usePatterns( blockName, rootClientId );
	const { data, fetch, reset, supportsPagination } = useRemoteData( {
		blockName,
		externallyManagedRemoteData: remoteDataAttribute,
		externallyManagedUpdateRemoteData: updateRemoteData,
		queryKey: queryKeySelected,
	} );
	const [ showPatternSelection, setShowPatternSelection ] = useState< boolean >( false );

	// Monitor queryInputs changes from parent
	useEffect( () => {
		void fetch( queryInputs ).then( () => {
			if ( innerBlocksPattern ) {
				insertPatternBlocks( innerBlocksPattern, supportsPagination );
				return;
			}

			setShowPatternSelection( true );
		} );
	}, [ queryInputs ] );

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
	}

	function onSelectPattern( pattern: BlockPattern ): void {
		insertPatternBlocks( pattern, true );
		setShowPatternSelection( false );
	}

	function updateRemoteData( remoteData?: RemoteData ): void {
		if ( hasRemoteDataChanged( remoteDataAttribute, remoteData ) ) {
			setAttributes( { remoteData } );
		}
	}

	function onUpdateQueryInputs( queryKey: string, inputs: RemoteDataQueryInput[] ): void {
		if ( ! remoteDataAttribute ) {
			return;
		}

		updateRemoteData( {
			...remoteDataAttribute,
			queryInputs: inputs,
			queryKey,
		} );
		onQueryInputsChange?.( inputs );
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
						selectors={ blockConfig.selectors }
					/>
				</InspectorControls>
			) }
			<div { ...blockProps }>
				<InnerBlocks />
			</div>
		</>
	);
}
