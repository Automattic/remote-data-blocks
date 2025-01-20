/**
 * WordPress dependencies
 */
import { useEffect, useMemo } from '@wordpress/element';
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

import './editor.scss';

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
	const { attributes, setAttributes, context, isSelected, clientId } = props;
	const { remoteData, index } = useRemoteDataContext( context );

	// Pass the content provided by bindings to save() via setAttributes with changed content
	const { content } = attributes;
	useEffect(() => {
		setAttributes( { content } );
	}, [ content ]);

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
	const hasRemoteDataContext = remoteData !== undefined;

	if ( ! hasBindings || ! hasRemoteDataContext ) {
		return <PlaceholderInstructions clientId={clientId} hasRemoteDataContext={hasRemoteDataContext} />;
	}

	return (
		<div>
			<SandBox
				html={ attributes.content }
				styles={ styles }
				title={ __( 'Remote Data Block HTML Preview' ) }
				tabIndex={ -1 }
			/>
			{ /* Similar to core/html, add an overlay register click events. */
			! isSelected && (
				<div className="remote-data-block-html-overlay"></div>
			) }
		</div>
	);
}

interface PlaceholderInstructionsProps {
	clientId: string;
	hasRemoteDataContext: boolean;
}

const PlaceholderInstructions = ( { clientId, hasRemoteDataContext }: PlaceholderInstructionsProps ) => {
	const { selectBlock } = useDispatch<BlockEditorStoreActions>( blockEditorStore );
	const { openGeneralSidebar } = useDispatch<EditPostStoreActions> ( editPostStore );

	const openSidebar = () => {
		selectBlock( clientId );
		openGeneralSidebar( 'edit-post/block' );
	};

	return <Placeholder
		label={__('Remote HTML')}
		instructions={__('Place this block in a remote data container and bind to an attribute to view HTML.')}
		style={{ margin: '1rem 0'}}
	>
		{ hasRemoteDataContext && <Button variant='primary' onClick={openSidebar}>Open sidebar</Button> }
	</Placeholder>;
}
