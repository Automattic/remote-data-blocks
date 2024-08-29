import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { BlockEditProps } from '@wordpress/blocks';
import { Spinner } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';

import { DataPanel } from './components/data-panel';
import { InnerBlocks } from './components/inner-blocks';
import { OverridesPanel } from './components/overrides-panel';
import { PatternSelection } from './components/pattern-selection';
import { Placeholder } from './components/placeholder';
import { DISPLAY_QUERY_KEY } from './config/constants';
import { usePatterns } from './hooks/use-patterns';
import { useRemoteData } from './hooks/use-remote-data';
import { hasRemoteDataChanged } from '../../utils/block-binding';
import { getBlockConfig } from '../../utils/localized-block-data';
import './editor.scss';

export function Edit( props: BlockEditProps< ContextBlockAttributes > ) {
	const blockConfig = getBlockConfig( props.name );

	if ( ! blockConfig ) {
		throw new Error( `Block configuration not found for block: ${ props.name }` );
	}

	const rootClientId = props.clientId;
	const blockProps = useBlockProps( {
		className: 'remote-data-container',
	} );

	const {
		getInnerBlocks,
		getSupportedPatterns,
		insertPatternBlocks,
		removeInnerBlocks,
		setShowPatternSelection,
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
			setShowPatternSelection( true );
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
		removeInnerBlocks();
		setShowPatternSelection( false );
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
						onCancel={ () => setShowPatternSelection( false ) }
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
