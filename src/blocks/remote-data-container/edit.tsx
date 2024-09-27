import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { BlockEditProps } from '@wordpress/blocks';
import { Spinner } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';

import { InnerBlocks } from '@/blocks/remote-data-container/components/inner-blocks';
import { DataPanel } from '@/blocks/remote-data-container/components/panels/data-panel';
import { OverridesPanel } from '@/blocks/remote-data-container/components/panels/overrides-panel';
import { PatternSelection } from '@/blocks/remote-data-container/components/pattern-selection';
import { Placeholder } from '@/blocks/remote-data-container/components/placeholder';
import {
	CONTAINER_CLASS_NAME,
	DISPLAY_QUERY_KEY,
} from '@/blocks/remote-data-container/config/constants';
import { usePatterns } from '@/blocks/remote-data-container/hooks/use-patterns';
import { useRemoteData } from '@/blocks/remote-data-container/hooks/use-remote-data';
import { hasRemoteDataChanged } from '@/utils/block-binding';
import { getBlockConfig } from '@/utils/localized-block-data';
import './editor.scss';

export function Edit( props: BlockEditProps< RemoteDataBlockAttributes > ) {
	const blockConfig = getBlockConfig( props.name );

	if ( ! blockConfig ) {
		throw new Error( `Block configuration not found for block: ${ props.name }` );
	}

	const rootClientId = props.clientId;
	const blockProps = useBlockProps( { className: CONTAINER_CLASS_NAME } );

	const {
		getInnerBlocks,
		getSupportedPatterns,
		insertPatternBlocks,
		markReadyForInsertion,
		resetReadyForInsertion,
		showPatternSelection,
	} = usePatterns( props.name, rootClientId );
	const { execute } = useRemoteData( props.name, DISPLAY_QUERY_KEY );
	const [ initialLoad, setInitialLoad ] = useState< boolean >( true );

	function fetchRemoteData( input: RemoteDataQueryInput, insertBlocks = true ) {
		execute( input, true )
			.then( remoteData => {
				if ( remoteData ) {
					updateRemoteData( remoteData, insertBlocks );
				}
			} )
			.catch( () => {} )
			.finally( () => {
				setInitialLoad( false );
			} );
	}

	// Update the remote data in the block attributes, which is passed via context
	// to children blocks. If this is the initial load of remote data, show the
	// pattern selection modal so that we can insert the blocks from the pattern.
	function updateRemoteData( remoteData: RemoteData, insertBlocks = false ) {
		if ( hasRemoteDataChanged( props.attributes.remoteData, remoteData ) ) {
			props.setAttributes( { remoteData } );
		}

		if ( insertBlocks ) {
			markReadyForInsertion();
		}
	}

	function refreshRemoteData() {
		if ( ! props.attributes.remoteData?.queryInput ) {
			return;
		}

		fetchRemoteData( props.attributes.remoteData.queryInput, false );
	}

	function resetRemoteData() {
		props.setAttributes( { remoteData: undefined } );
		resetReadyForInsertion();
	}

	useEffect( () => {
		// Refetch remote data for initial load
		refreshRemoteData();
	}, [] );

	// No remote data has been selected yet, show a placeholder.
	if ( ! props.attributes.remoteData ) {
		return (
			<div { ...blockProps }>
				<Placeholder blockConfig={ blockConfig } fetchRemoteData={ fetchRemoteData } />
			</div>
		);
	}

	if ( showPatternSelection ) {
		const supportedPatterns = getSupportedPatterns( props.attributes.remoteData?.results[ 0 ] );

		if ( supportedPatterns.length ) {
			return (
				<div { ...blockProps }>
					<PatternSelection
						insertPatternBlocks={ insertPatternBlocks }
						onCancel={ resetReadyForInsertion }
						supportedPatterns={ supportedPatterns }
					/>
				</div>
			);
		}
	}

	return (
		<>
			<InspectorControls>
				<OverridesPanel
					blockConfig={ blockConfig }
					remoteData={ props.attributes.remoteData }
					updateRemoteData={ updateRemoteData }
				/>
				<DataPanel
					refreshRemoteData={ refreshRemoteData }
					remoteData={ props.attributes.remoteData }
					resetRemoteData={ resetRemoteData }
				/>
			</InspectorControls>

			<div { ...blockProps }>
				{ initialLoad && (
					<div className="remote-data-blocks-loading-overlay">
						<Spinner
							style={ {
								height: '50px',
								width: '50px',
							} }
						/>
					</div>
				) }
				<InnerBlocks
					blockConfig={ blockConfig }
					getInnerBlocks={ getInnerBlocks }
					remoteData={ props.attributes.remoteData }
				/>
			</div>
		</>
	);
}
