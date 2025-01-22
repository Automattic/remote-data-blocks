import { Button, ExternalLink } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { copy } from '@wordpress/icons';
import { Prism as SyntaxHighlighter } from 'react-syntax-highlighter';
import { coy } from 'react-syntax-highlighter/dist/esm/styles/prism';

import { useDataSources } from '../hooks/useDataSources';

const CodeSnippet = ( { code }: { code: string } ) => {
	const { showSnackbar } = useDataSources();

	const handleCopy = () => {
		navigator.clipboard
			.writeText( code )
			.then( () => {
				showSnackbar( 'success', __( 'Code copied to clipboard!', 'remote-data-blocks' ) );
			} )
			.catch( () => {
				showSnackbar( 'error', __( 'Failed to copy code', 'remote-data-blocks' ) );
			} );
	};

	return (
		<div style={ { padding: '0 24px' } }>
			<p style={ { marginBottom: '24px' } }>
				{ __(
					"Below, you'll find the code used to register the block for this data source, which can be used as a reference for extending the data source.\nTo get started, copy the code below and add it to your plugin directory. "
				) }
				<ExternalLink href="https://remotedatablocks.com/docs/extending/index/">
					{ __( 'Learn more about extending', 'remote-data-blocks' ) }
				</ExternalLink>
			</p>

			<div
				style={ {
					position: 'relative',
					marginBottom: '1rem',
					padding: '16px',
					borderRadius: 'var(--radius-s, 2px)',
					border: '1px solid var(--Alias-border-border-input, #AEAEAE)',
				} }
			>
				<Button
					onClick={ handleCopy }
					icon={ copy }
					variant="tertiary"
					style={ { position: 'absolute', top: '8px', right: '8px', zIndex: '11' } }
				>
					{ __( 'Copy' ) }
				</Button>
				<SyntaxHighlighter language="php" style={ coy } showLineNumbers>
					{ code }
				</SyntaxHighlighter>
			</div>
		</div>
	);
};

export default CodeSnippet;
