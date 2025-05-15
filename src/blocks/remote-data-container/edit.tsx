import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { BlockEditProps } from '@wordpress/blocks';
import { useState } from '@wordpress/element';

import { QuerySelectionPlaceholder } from '@/blocks/remote-data-container/components/placeholders/QuerySelectionPlaceholder';
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
	const [ queryKey, setQueryKey ] = useState< string >( remoteDataAttribute?.queryKey ?? '' );

	// const [ queryInputs, setQueryInputs ] = useState< RemoteDataQueryInput[] >(
	// 	remoteDataAttribute?.queryInputs ?? [ {} ]
	// );

	// console.log( 'remoteDataAttribute', remoteDataAttribute );

	// function setAttributes( attributes: RemoteDataBlockAttributes ): void {
	// 	props.setAttributes( attributes );
	// }

	console.log( 'queryKey', queryKey );

	if ( ! queryKey ) {
		return <QuerySelectionPlaceholder blockConfig={ blockConfig } onSelect={ setQueryKey } />;
	}

	return (
		<>
			<div { ...blockProps }>
				<InnerBlocks />
			</div>
		</>
	);
}
