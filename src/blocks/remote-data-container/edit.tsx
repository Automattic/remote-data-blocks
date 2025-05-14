import { BlockEditProps } from '@wordpress/blocks';
import { useState } from '@wordpress/element';

import { QueryComponent } from './components/QueryComponent';
import { Placeholder } from '@/blocks/remote-data-container/components/placeholders/Placeholder';
import { getBlockConfig } from '@/utils/localized-block-data';
import { migrateRemoteData } from '@/utils/remote-data';

import './editor.scss';

export function Edit( props: BlockEditProps< RemoteDataBlockAttributes > ) {
	const blockName = props.name;
	const blockConfig = getBlockConfig( blockName );

	if ( ! blockConfig ) {
		throw new Error( `Block configuration not found for block: ${ blockName }` );
	}

	const remoteDataAttribute = migrateRemoteData( props.attributes.remoteData );

	const [ queryKeySelected, setQueryKeySelected ] = useState< string >(
		remoteDataAttribute?.queryKey ?? ''
	);
	const [ queryInputs, setQueryInputs ] = useState< RemoteDataQueryInput[] >(
		remoteDataAttribute?.queryInputs ?? []
	);

	console.log( 'remoteDataAttribute', remoteDataAttribute );

	function initializeRemoteData( queryKey: string ): void {
		setQueryKeySelected( queryKey );
		console.log( 'Initializing remote data for query key', queryKey );
	}

	return (
		<>
			{ queryKeySelected && (
				<QueryComponent
					blockConfig={ blockConfig }
					blockName={ blockName }
					queryKeySelected={ queryKeySelected }
					rootClientId={ props.clientId }
					remoteDataAttribute={ remoteDataAttribute }
					setAttributes={ props.setAttributes }
					queryInputs={ queryInputs }
				/>
			) }
			{ ! queryKeySelected && (
				<Placeholder
					blockConfig={ blockConfig }
					initializeRemoteData={ initializeRemoteData }
					onSelect={ setQueryInputs }
				/>
			) }
		</>
	);
}
