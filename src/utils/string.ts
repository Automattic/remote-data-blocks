/**
 * Converts a string to title case
 * @param str string to convert
 * @returns title cased string
 */
export const toTitleCase = ( str: string ): string => {
	return str.replace( /\w\S*/g, txt => {
		return txt.charAt( 0 ).toUpperCase() + txt.substring( 1 ).toLowerCase();
	} );
};

/**
 * Casts a string to JSON
 * @param value string to cast
 * @returns parsed JSON or null
 */
export function safeParseJSON< T = unknown >( value: unknown ): T | null {
	if ( 'undefined' === typeof value || null === value ) {
		return null;
	}

	if ( 'string' === typeof value && value.trim().length === 0 ) {
		return null;
	}

	if ( 'string' === typeof value ) {
		try {
			return JSON.parse( value ) as T;
		} catch ( error ) {
			return null;
		}
	}

	return null;
}
