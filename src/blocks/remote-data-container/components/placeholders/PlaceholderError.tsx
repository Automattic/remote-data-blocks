import { Button, Placeholder, __experimentalHStack as HStack, Icon } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { caution } from '@wordpress/icons';

import { RemoteDataFetchError } from '../../hooks/useRemoteData';

interface PlaceholderErrorProps {
	blockName: string;
	error: Error;
	onTryAgain: () => void;
}

export function PlaceholderError( { blockName, error, onTryAgain }: PlaceholderErrorProps ) {
	const [ showErrorDetails, setShowErrorDetails ] = useState( false );

	let message = error.message;
	if ( error instanceof RemoteDataFetchError && error.cause instanceof Error ) {
		message = `${ message }: ${ error.cause.message }`;
	}

	return (
		<Placeholder
			className="remote-data-container-error"
			icon={ caution }
			label={ sprintf( __( 'Cannot connect to remote data source: (%s)' ), blockName ) }
		>
			<Icon className="remote-data-container-error__icon-overlay" icon={ caution } />
			<HStack justify="flex-start">
				<Button variant="secondary" onClick={ onTryAgain }>
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
					{ message }
				</code>
			) }
		</Placeholder>
	);
}
