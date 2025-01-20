/**
 * WordPress dependencies
 */
import { useMemo } from '@wordpress/element';
import {
	transformStyles,
	store as blockEditorStore,
	EditorStyle,
	BlockEditorStoreActions,
	BlockEditorStoreSelectors,
} from '@wordpress/block-editor';
import { store as editPostStore, EditPostStoreActions } from '@wordpress/edit-post';
import { SandBox, Placeholder, Button } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@/utils/i18n';
import { useRemoteDataContext } from '@/blocks/remote-data-container/hooks/useRemoteDataContext';
import { BlockEditProps } from '@wordpress/blocks';

// This block is based on the official core/html block from Gutenberg.
// The main difference is that this block uses HTML from remote data block context instead of user input.

// Default styles used to unset some of the styles
// that might be inherited from the editor style.
const DEFAULT_STYLES = `
	html,body,:root {
		margin: 0 !important;
		padding: 0 !important;
		overflow: visible !important;
		min-height: auto !important;
	}
`;

export default function RemoteDataHTML( props: BlockEditProps< RemoteDataInnerBlockAttributes > ): JSX.Element {
	const { attributes, context, isSelected, clientId } = props;
	const { remoteData, index } = useRemoteDataContext( context );

	const settingStyles = useSelect< BlockEditorStoreSelectors, EditorStyle[] >(
		select => select( blockEditorStore ).getSettings().styles,
		[]
	);

	const styles = useMemo(
		() => [
			DEFAULT_STYLES,
			...transformStyles(
				( settingStyles ?? [] ).filter( ( style: EditorStyle ) => style.css)
			),
		],
		[ settingStyles ]
	);

	const hasBindings = attributes?.metadata?.bindings?.content !== undefined;

	if ( ! hasBindings ) {
		return <PlaceholderInstructions clientId={clientId}/>;
	}

	return (
		<>
			<SandBox
				html={ attributes.content }
				styles={ styles }
				title={ __( 'Remote Data Block HTML Preview' ) }
				tabIndex={ -1 }
			/>
			{ /*
				An overlay is added when the block is not selected in order to register click events.
				Some browsers do not bubble up the clicks from the sandboxed iframe, which makes it
				difficult to reselect the block.
			*/ }
			{ ! isSelected && (
				<div className="block-library-html__preview-overlay"></div>
			) }
		</>
	);
}

interface PlaceholderInstructionsProps {
	clientId: string;
}

const PlaceholderInstructions = ( { clientId }: PlaceholderInstructionsProps ) => {
	const { selectBlock } = useDispatch<BlockEditorStoreActions>( blockEditorStore );
	const { openGeneralSidebar } = useDispatch<EditPostStoreActions> ( editPostStore );

	const openSidebar = () => {
		selectBlock( clientId );
		openGeneralSidebar( 'edit-post/block' );
	};

	return <Placeholder
		label={__('Remote HTML')}
		instructions={__('Place this block in a remote data container and connect to view HTML.')}
	>
		<Button variant='primary' onClick={openSidebar}>Open sidebar</Button>
	</Placeholder>;
}
