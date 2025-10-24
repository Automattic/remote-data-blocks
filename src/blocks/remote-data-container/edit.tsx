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

	if ( ! blockConfig ) {
		throw new Error( `Block configuration not found for block: ${ blockName }` );
	}

	const rootClientId = props.clientId;
	const remoteDataAttribute = migrateRemoteData( props.attributes.remoteData );

	const [ displayQueryKey, setDisplayQueryKey ] = useState< string >(
		remoteDataAttribute?.displayQueryKey ?? ''
	);

	const [ queryInputs, setQueryInputs ] = useState< RemoteDataQueryInput[] >(
		remoteDataAttribute?.queryInputs ?? []
	);

	function setAttributes( attributes: RemoteDataBlockAttributes ): void {
		props.setAttributes( attributes );
	}

	function resetQuery(): void {
		setDisplayQueryKey( '' );
		setQueryInputs( [] );
	}

	if ( ! displayQueryKey ) {
		return (
			<QuerySelectionPlaceholder
				blockConfig={ blockConfig }
				onDisplayQueryKeySelect={ setDisplayQueryKey }
				onQueryInputsSelect={ setQueryInputs }
			/>
		);
	}

	return (
		<>
			<EditErrorBoundary blockTitle={ blockName }>
				<QueryComponent
					blockConfig={ blockConfig }
					blockName={ blockName }
					displayQueryKey={ displayQueryKey }
					queryInputs={ queryInputs }
					setAttributes={ setAttributes }
					rootClientId={ rootClientId }
					remoteDataAttribute={ remoteDataAttribute }
					resetQuery={ resetQuery }
				/>
			</EditErrorBoundary>
		</>
	);
}
