import { addFilter } from '@wordpress/hooks';

import { withPreviewIndex } from './withPreviewIndex';

/**
 * Use a filter to wrap the block edit component and inject the preview index
 * when we are rendering the template block for collections.
 */
addFilter( 'editor.BlockEdit', 'remote-data-blocks/withPreviewIndex', withPreviewIndex );
