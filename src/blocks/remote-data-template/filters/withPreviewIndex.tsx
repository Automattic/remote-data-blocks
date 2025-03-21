import { BlockEditProps } from '@wordpress/blocks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { useContext } from '@wordpress/element';

import { PreviewIndexContext } from '../context/PreviewIndexContext';

export const withPreviewIndex = createHigherOrderComponent( BlockEdit => {
	return ( props: BlockEditProps< RemoteDataInnerBlockAttributes > ) => {
		const previewIndex = useContext( PreviewIndexContext );
		return <BlockEdit { ...props } previewIndex={ previewIndex } />;
	};
}, 'withPreviewIndex' );
