/**
 * WordPress dependencies
 */
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import { BlockEditProps, Template } from '@wordpress/blocks';
import { Placeholder } from '@wordpress/components';
import { blockDefault } from '@wordpress/icons';

import { useRemoteDataContext } from '@/blocks/remote-data-container/hooks/useRemoteDataContext';
import { __ } from '@/utils/i18n';

import './editor.scss';

const NO_RESULTS_TEMPLATE: Template[] = [
	[
		'core/paragraph',
		{
			content: __( 'No results found.' ),
		},
	],
];

export function Edit( props: BlockEditProps< RemoteDataNoResultBlockAttributes > ): JSX.Element {
	const { context } = props;
	const { remoteData } = useRemoteDataContext( context );
	const blockProps = useBlockProps();

	if ( ! remoteData?.blockName ) {
		return (
			<Placeholder
				icon={ blockDefault }
				label={ __(
					'This block only works when placed inside a remote data block when there are no results. This block will be ignored as currently configured.'
				) }
			/>
		);
	}

	return (
		<div { ...blockProps }>
			<InnerBlocks template={ NO_RESULTS_TEMPLATE } />
		</div>
	);
}
