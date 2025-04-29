/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { BlockEditProps } from '@wordpress/blocks';
import { Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { useRemoteDataContext } from '@/blocks/remote-data-container/hooks/useRemoteDataContext';
import { LoopTemplate } from '@/blocks/remote-data-template/components/loop-template/LoopTemplate';
import { useGetInnerBlocks } from '@/blocks/remote-data-template/hooks/useGetInnerBlocks';

import './editor.scss';

export function Edit( props: BlockEditProps< RemoteDataTemplateBlockAttributes > ): JSX.Element {
	const { clientId, context, name } = props;
	const blockProps = useBlockProps();

	const { remoteData } = useRemoteDataContext( context );
	const getInnerBlocks = useGetInnerBlocks( name, clientId, remoteData?.blockName );

	if ( ! remoteData?.blockName ) {
		return (
			<div { ...blockProps }>
				<Placeholder
					label={ __( 'Remote Data Template' ) }
					instructions={ __(
						'This block only works when placed inside a remote data block container and bound to an attribute. This block will be ignored as currently configured.'
					) }
				/>
			</div>
		);
	}

	// Don't render anything if there are no results.
	// Leave it to the no results block to handle this.
	if ( ! remoteData.results.length ) {
		return <div { ...blockProps } />;
	}

	return <LoopTemplate getInnerBlocks={ getInnerBlocks } remoteData={ remoteData } />;
}
