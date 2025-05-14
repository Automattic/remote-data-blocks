import { useBlockProps } from '@wordpress/block-editor';
import { BlockEditProps } from '@wordpress/blocks';

import { InnerBlocks } from '@/blocks/remote-data-container/components/InnerBlocks';
import { Placeholder } from '@/blocks/remote-data-container/components/placeholders/Placeholder';
import { CONTAINER_CLASS_NAME } from '@/blocks/remote-data-container/config/constants';
import { getBlockConfig } from '@/utils/localized-block-data';
import { migrateRemoteData } from '@/utils/remote-data';

import './editor.scss';

export function Edit( props: BlockEditProps< RemoteDataBlockAttributes > ) {
	const blockName = props.name;
	const blockConfig = getBlockConfig( blockName );

	if ( ! blockConfig ) {
		throw new Error( `Block configuration not found for block: ${ blockName }` );
	}

	const blockProps = useBlockProps( { className: CONTAINER_CLASS_NAME } );
	const remoteDataAttribute = migrateRemoteData( props.attributes.remoteData );

	// const { getSupportedPatterns, innerBlocksPattern, insertPatternBlocks, resetInnerBlocks } =
	// 	usePatterns( blockName, rootClientId );

	// const { data, fetch, loading, reset, supportsPagination } = useRemoteData( {
	// 	blockName,
	// 	externallyManagedRemoteData: remoteDataAttribute,
	// 	externallyManagedUpdateRemoteData: updateRemoteData,
	// 	queryKey: '',
	// } );

	// const [ showPatternSelection, setShowPatternSelection ] = useState< boolean >( false );

	function initializeRemoteData( queryKey: string ): void {
		console.log( 'Initializing remote data for query key', queryKey );
	}

	// function refreshRemoteData(): void {
	// 	void fetch( remoteDataAttribute?.queryInputs ?? [ {} ] );
	// }

	// function resetPatternSelection(): void {
	// 	resetInnerBlocks();
	// 	setShowPatternSelection( false );
	// }

	// function resetRemoteData(): void {
	// 	reset();
	// 	resetPatternSelection();
	// }

	// function onSelectPattern( pattern: BlockPattern ): void {
	// 	insertPatternBlocks( pattern, true );
	// 	setShowPatternSelection( false );
	// }

	// function onSelectRemoteData( inputs: RemoteDataQueryInput[] ): void {
	// 	void fetch( inputs ).then( () => {
	// 		if ( innerBlocksPattern ) {
	// 			insertPatternBlocks( innerBlocksPattern, supportsPagination );
	// 			return;
	// 		}

	// 		setShowPatternSelection( true );
	// 	} );
	// }

	// function updateRemoteData( remoteData?: RemoteData ): void {
	// 	if ( hasRemoteDataChanged( remoteDataAttribute, remoteData ) ) {
	// 		props.setAttributes( { remoteData } );
	// 	}
	// }

	// function onUpdateQueryInputs( queryKey: string, inputs: RemoteDataQueryInput[] ): void {
	// 	if ( ! remoteDataAttribute ) {
	// 		return;
	// 	}

	// 	updateRemoteData( {
	// 		...remoteDataAttribute,
	// 		queryInputs: inputs,
	// 		queryKey,
	// 	} );
	// 	refreshRemoteData();
	// }

	// No remote data has been selected yet, show a placeholder.
	if ( ! remoteDataAttribute?.queryKey ) {
		return (
			<div { ...blockProps }>
				<Placeholder blockConfig={ blockConfig } initializeRemoteData={ initializeRemoteData } />
			</div>
		);
	}

	// if ( showPatternSelection ) {
	// 	const supportedPatterns = getSupportedPatterns( data.results[ 0 ] );

	// 	return (
	// 		<div { ...blockProps }>
	// 			<PatternSelection
	// 				blockName={ blockName }
	// 				onCancel={ resetPatternSelection }
	// 				onSelectPattern={ onSelectPattern }
	// 				supportedPatterns={ supportedPatterns }
	// 			/>
	// 		</div>
	// 	);
	// }

	return (
		<>
			{ /* <InspectorControls>
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
			</InspectorControls> */ }

			<div { ...blockProps }>
				{ /* { loading && (
					<div className="remote-data-blocks-loading-overlay">
						<Spinner
							style={ {
								height: '50px',
								width: '50px',
							} }
						/>
					</div>
				) } */ }
				<InnerBlocks />
			</div>
		</>
	);
}
