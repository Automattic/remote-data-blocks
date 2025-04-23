/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { BlockEditProps } from '@wordpress/blocks';
import { Placeholder } from '@wordpress/components';

import { useRemoteDataContext } from '@/blocks/remote-data-container/hooks/useRemoteDataContext';
import { __ } from '@/utils/i18n';

import './editor.scss';

export function Edit( props: BlockEditProps< RemoteDataEmptyResultBlockAttributes > ): JSX.Element {
	const { context } = props;
	const blockProps = useBlockProps();

	const { remoteData } = useRemoteDataContext( context );

	if ( ! remoteData?.results ) {
		return (
			<div { ...blockProps }>
				<Placeholder
					label={ __( 'Remote Data Empty Result' ) }
					instructions={ __(
						'This block only works when placed inside a remote data block when there are no results. This block will be ignored as currently configured.'
					) }
				/>
			</div>
		);
	}

	if ( remoteData?.results.length === 0 ) {
		return (
			<div { ...blockProps }>
				<div className="remote-data-empty-result">
					<p>Empty Result</p>
				</div>
			</div>
		);
	}

	return <div { ...blockProps } />;
}
