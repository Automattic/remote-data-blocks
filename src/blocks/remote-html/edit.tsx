/**
 * WordPress dependencies
 */
import {
	transformStyles,
	store as blockEditorStore,
	EditorStyle,
	BlockEditorStoreSelectors,
	useBlockProps,
} from '@wordpress/block-editor';
import { BlockEditProps } from '@wordpress/blocks';
import { Placeholder } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

import { IsolatedPreview } from './IsolatedPreview';
import { REMOTE_DATA_CONTEXT_KEY } from '@/blocks/remote-data-container/config/constants';
import { BLOCK_BINDING_SOURCE, STORE_NAME as rdbStore } from '@/config/constants';
import { __ } from '@/utils/i18n';
import { resolveRemoteBindingValue } from '@/utils/resolve-remote-binding-value';

import type { Selectors } from '@/store';

import './editor.scss';

/**
 * This block is based on the official core/html block from Gutenberg.
 * The main difference is that this block binds to HTML from remote data block context instead of user input.
 */

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

export function Edit( props: BlockEditProps< RemoteDataInnerBlockAttributes > ): React.JSX.Element {
	const { attributes, context, isSelected, name } = props;
	const blockProps = useBlockProps();
	const { content } = attributes;
	const binding = attributes.metadata?.bindings?.content;
	const remoteData = context[ REMOTE_DATA_CONTEXT_KEY ] as RemoteData | undefined;
	const boundContent = useSelect< Selectors >(
		select => {
			if ( binding?.source !== BLOCK_BINDING_SOURCE ) {
				return undefined;
			}

			// Core skips this raw attribute because it is outside its supported bindings.
			return resolveRemoteBindingValue( {
				args: binding.args,
				blockName: name,
				target: 'content',
				remoteData,
				previewIndex: select( rdbStore )?.getPreviewIndex( remoteData?.resultId ),
				getSavedValue: () => content,
			} );
		},
		[ binding, content, name, remoteData ]
	);

	const settingStyles = useSelect< BlockEditorStoreSelectors, EditorStyle[] >(
		select => select( blockEditorStore ).getSettings().styles,
		[]
	);

	const styles = [
		DEFAULT_STYLES,
		...transformStyles( ( settingStyles ?? [] ).filter( ( style: EditorStyle ) => style.css ) ),
	];

	const hasBindings = binding !== undefined;
	const contentString = ( boundContent ?? content )?.toString() ?? '';
	const hasContent = contentString.length > 0;

	if ( ! hasBindings && ! hasContent ) {
		return (
			<div { ...blockProps }>
				<PlaceholderInstructions />
			</div>
		);
	}

	return (
		<div { ...blockProps }>
			<IsolatedPreview
				html={ contentString }
				styles={ styles }
				title={ __( 'Remote Data Block HTML Preview' ) }
			/>
			{
				/**
				 * When content is rendered in a Sandbox, clicks on the block's content are consumed
				 * by the inner DOM and are not registered in the editor. Adding an invisible overlay
				 * allows this block to be selected in the editor when clicked.
				 */
				! isSelected && <div className="remote-data-block-html-overlay"></div>
			}
		</div>
	);
}

const PlaceholderInstructions = () => {
	return (
		<Placeholder
			label={ __( 'Remote HTML' ) }
			instructions={ __(
				'This block only works when placed inside a remote data block container and bound to an attribute. This block will be ignored as currently configured.'
			) }
		/>
	);
};
