import { Button, Icon, Placeholder, __experimentalHStack as HStack } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { error as errorIcon } from '@wordpress/icons';

import './PlaceholderError.scss';

interface PlaceholderErrorProps {
	blockTitle: string;
	error: Error;
	onRetry: () => void;
}

export function PlaceholderError( { blockTitle, error, onRetry }: PlaceholderErrorProps ) {
	const [ showErrorDetails, setShowErrorDetails ] = useState< boolean >( false );

	return (
		<Placeholder
			className="remote-data-container-error"
			icon={ errorIcon }
			label={ sprintf( __( 'Remote data source error (%s)' ), blockTitle ) }
		>
			<Icon className="remote-data-container-error__icon-overlay" icon={ errorIcon } />
			<HStack justify="flex-start">
				<Button variant="secondary" onClick={ onRetry }>
					{ __( 'Try again' ) }
				</Button>
				<Button variant="tertiary" onClick={ () => setShowErrorDetails( ! showErrorDetails ) }>
					{ sprintf( __( '%s error details' ), showErrorDetails ? 'Hide' : 'Show' ) }
				</Button>
			</HStack>
			{ showErrorDetails && (
				<code
					className={ `remote-data-container-error__content is-${
						showErrorDetails ? 'open' : 'closed'
					}` }
				>
					{ error.message }
				</code>
			) }
		</Placeholder>
	);
}
