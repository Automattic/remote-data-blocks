import { InnerBlocks as CoreInnerBlocks } from '@wordpress/block-editor';

// This component wraps the Core InnerBlocks component to enable the renderAppender.
export function InnerBlocks() {
	return <CoreInnerBlocks renderAppender={ CoreInnerBlocks.DefaultBlockAppender } />;
}
