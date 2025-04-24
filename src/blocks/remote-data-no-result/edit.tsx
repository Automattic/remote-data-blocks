/**
 * WordPress dependencies
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { BlockEditProps, Template } from '@wordpress/blocks';

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

const NO_RESULTS_PLACEHOLDER_TEMPLATE: Template[] = [
	[
		'core/paragraph',
		{
			placeholder: __(
				'This block only works when placed inside a remote data block when there are no results. This block will be ignored as currently configured.'
			),
		},
	],
];

export function Edit( props: BlockEditProps< RemoteDataNoResultBlockAttributes > ): JSX.Element {
	const { context } = props;
	const { remoteData } = useRemoteDataContext( context );
	const blockProps = useBlockProps();

	// This is mirroring the query-no-results block from Gutenberg.
	// https://github.com/WordPress/gutenberg/blob/trunk/packages/block-library/src/query-no-results/edit.js
	let templateToUse = NO_RESULTS_PLACEHOLDER_TEMPLATE;

	if ( remoteData?.results?.length === 0 ) {
		templateToUse = NO_RESULTS_TEMPLATE;
	} else if ( remoteData?.results ) {
		templateToUse = [];
	}

	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template: templateToUse,
	} );

	return <div { ...innerBlocksProps } />;
}
