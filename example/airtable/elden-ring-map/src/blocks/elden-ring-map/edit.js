import { BlockControls, useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

import { Map } from './map';
import { __ } from '@/utils/i18n';

export function Edit( { context } ) {
	const blockProps = useBlockProps();
	const innerBlocksProps = useInnerBlocksProps( blockProps );

	const remoteData = context[ 'remote-data-blocks/remoteData' ];
	const coordinates = remoteData?.results || [];

	if ( ! remoteData ) {
		return (
			<p style={ { color: 'red', padding: '20px' } }>
				{ __( 'This block only supports being rendered inside of an Elden Ring Map Query block.' ) }
			</p>
		);
	}

	return (
		<>
			<BlockControls />
			<div { ...innerBlocksProps }>
				<Map coordinates={ coordinates } />
			</div>
		</>
	);
}
