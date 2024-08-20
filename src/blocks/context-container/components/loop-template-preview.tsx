import { __experimentalUseBlockPreview as useBlockPreview } from '@wordpress/block-editor';
import { BlockInstance } from '@wordpress/blocks';
import { memo } from '@wordpress/element';

interface LoopTemplatePreviewProps {
	blocks: BlockInstance[];
	isHidden: boolean;
	onActive: () => void;
}

// Use the experimental block preview hook to render a preview of blocks when
// they are not being actively edited. This preview is not interactive and are
// not "real" blocks so they don't show up in the outline view.
//
// We hide the preview for the blocks that are being edited so they don't
// duplicate.
//
// This is a mimick of the PostTemplate component from Gutenberg core.
export function LoopTemplatePreview( { blocks, isHidden, onActive }: LoopTemplatePreviewProps ) {
	const blockPreviewProps = useBlockPreview( {
		blocks,
		props: {},
	} );

	const style = {
		display: isHidden ? 'none' : undefined,
	};

	return (
		<li
			{ ...blockPreviewProps }
			tabIndex={ 0 }
			// eslint-disable-next-line jsx-a11y/no-noninteractive-element-to-interactive-role
			role="button"
			onClick={ onActive }
			onKeyDown={ onActive }
			style={ style }
		/>
	);
}

export const MemoizedLoopTemplatePreview = memo( LoopTemplatePreview );
