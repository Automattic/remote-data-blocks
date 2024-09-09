// Provide functions to consistently generate class names.
export function getClassName( name: string, existingClassName?: string ): string {
	return combineClassNames( existingClassName, `rdb-${ toKebabCase( name ) }` );
}

export function combineClassNames( ...classNames: ( string | undefined )[] ): string {
	return classNames.filter( Boolean ).join( ' ' );
}

export function toKebabCase( str: string ): string {
	return str.replace( /[^a-zA-Z\d\u00C0-\u00FF]/g, '-' ).toLowerCase();
}

export function toTitleCase( str: string ): string {
	return str.replace( /\w\S*/g, txt => {
		return txt.charAt( 0 ).toUpperCase() + txt.substring( 1 ).toLowerCase();
	} );
}
