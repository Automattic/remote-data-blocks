import { BlockEditProps } from '@wordpress/blocks';
import { useState } from '@wordpress/element';

import { EditErrorBoundary } from './components/EditErrorBoundary';
import { QueryComponent } from './components/QueryComponent';
import { QuerySelectionPlaceholder } from '@/blocks/remote-data-container/components/placeholders/QuerySelectionPlaceholder';
import { getBlockConfig } from '@/utils/localized-block-data';
import { migrateRemoteData } from '@/utils/remote-data';

import './editor.scss';

export function Edit( props: BlockEditProps< RemoteDataBlockAttributes > ): JSX.Element {
	const blockName = props.name;
	const blockConfig = getBlockConfig( blockName );

	const { displayQuery } = props.attributes;

	if ( ! blockConfig ) {
		throw new Error( `Block configuration not found for block: ${ blockName }` );
	}

	const rootClientId = props.clientId;
	const remoteDataAttribute = migrateRemoteData( props.attributes.remoteData );

	// const [ queryGroup, setQueryGroup ] = useState< string >( remoteDataAttribute?.queryGroup ?? '' );

	const [ queryInputs, setQueryInputs ] = useState< RemoteDataQueryInput[] >(
		remoteDataAttribute?.queryInputs ?? []
	);

	function resetQuery(): void {
		// setQueryGroup( '' );
		setQueryInputs( [] );
	}

	if ( ! displayQuery ) {
		return (
			<QuerySelectionPlaceholder
				blockConfig={ blockConfig }
				onDisplayQuerySelected={ newDisplayQuery =>
					props.setAttributes( { displayQuery: newDisplayQuery } )
				}
			/>
		);
	}

	return (
		<>
			<EditErrorBoundary blockTitle={ blockName }>
				<QueryComponent
					blockConfig={ blockConfig }
					blockName={ blockName }
					queryGroup={ '' }
					queryInputs={ queryInputs }
					setAttributes={ props.setAttributes }
					rootClientId={ rootClientId }
					remoteDataAttribute={ remoteDataAttribute }
					resetQuery={ resetQuery }
				/>
			</EditErrorBoundary>
		</>
	);
}
