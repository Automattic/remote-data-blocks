export abstract class DisplayableError extends Error {
	public abstract toString(): string;
}

export function ensureError( error: unknown ): Error {
	if ( error instanceof Error ) {
		return error;
	}

	if ( null === error || undefined === error ) {
		return new Error( 'An unknown error occurred' );
	}

	if ( typeof error === 'string' ) {
		return new Error( error );
	}

	if ( 'object' === typeof error && 'message' in error ) {
		return new Error( String( error.message ) );
	}

	return new Error( String( error ) );
}
