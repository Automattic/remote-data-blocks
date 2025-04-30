/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { BlockEditProps } from '@wordpress/blocks';
import { Placeholder } from '@wordpress/components';
import { blockDefault } from '@wordpress/icons';

import { useRemoteDataContext } from '@/blocks/remote-data-container/hooks/useRemoteDataContext';
import { __ } from '@/utils/i18n';

import './editor.scss';

export function Edit( props: BlockEditProps< RemoteDataPaginationBlockAttributes > ): JSX.Element {
	const { context } = props;
	const blockProps = useBlockProps();

	const { remoteData } = useRemoteDataContext( context );

	if ( ! remoteData?.blockName ) {
		return (
			<Placeholder
				label={ __( 'Remote Data Pagination' ) }
				icon={ blockDefault }
				instructions={ __(
					'This block must be placed inside a remote data block. This block will be ignored as currently configured.'
				) }
			/>
		);
	}

	if ( ! remoteData?.pagination ) {
		return (
			<div { ...blockProps }>
				<Placeholder
					label={ __( 'Remote Data Pagination' ) }
					instructions={ __(
						'This block only works when placed inside a remote data block using data that supports pagination. This block will be ignored as currently configured.'
					) }
				/>
			</div>
		);
	}

	const labelPrevious = 'Previous';
	const labelNext = 'Next';

	// Loosely follows the core query-pagination-* blocks:
	// https://github.com/WordPress/gutenberg/blob/268409673287c1effc6ae2acda720d3015f0f6f0/packages/block-library/src/query-pagination-previous/edit.js
	//
	// One key difference is that we are registering a single pagination block
	// instead of separate previous, next, and page number blocks.
	return (
		<div { ...blockProps }>
			<div className="remote-data-pagination">
				<a href="#pagination-previous-pseudo-link" onClick={ event => event.preventDefault() }>
					<span>{ labelPrevious }</span>
				</a>
				<a href="#pagination-next-pseudo-link" onClick={ event => event.preventDefault() }>
					<span>{ labelNext }</span>
				</a>
			</div>
		</div>
	);
}
