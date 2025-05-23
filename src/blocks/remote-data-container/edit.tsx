import { BlockEditProps } from '@wordpress/blocks';
import { useState } from '@wordpress/element';

import { EditErrorBoundary } from './components/EditErrorBoundary';
import { QueryComponent } from './components/QueryComponent';
import { QuerySelectionPlaceholder } from '@/blocks/remote-data-container/components/placeholders/QuerySelectionPlaceholder';
import { getBlockConfig } from '@/utils/localized-block-data';
import { migrateRemoteData } from '@/utils/remote-data';
import { cloud } from '@wordpress/icons';

import './editor.scss';
import { ItemSelectQueryType } from './components/placeholders/ItemSelectQueryType';
import { Placeholder } from '@wordpress/components';

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

	if ( displayQuery && ! queryInputs.length ) {
		return (
			<Placeholder
				icon={ cloud }
				label={ blockConfig.settings.title }
				instructions={
					blockConfig.instructions ??
					__( 'This block requires selection of one or more items for display.' )
				}
			>
				<ItemSelectQueryType
					blockName={ blockConfig.name }
					selectors={ blockConfig.selectors }
					onSelect={ setQueryInputs }
				/>
			</Placeholder>
		);
	}

	return (
		<>
			<EditErrorBoundary blockTitle={ blockName }>
				<QueryComponent
					blockConfig={ blockConfig }
					blockName={ blockName }
					queryGroup={ displayQuery }
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
