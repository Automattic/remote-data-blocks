export const isNonEmptyObj = ( obj: unknown ): boolean =>
	typeof obj === 'object' && obj !== null && Object.keys( obj ).length > 0;

export const constructObjectWithValues = < T >(
	obj: Record< string, unknown >,
	defaultValue: T
): Record< string, T > => {
	return Object.fromEntries( Object.keys( obj ).map( key => [ key, defaultValue ] ) );
};
