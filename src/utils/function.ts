export function memoizeFn< T extends ( ...args: Parameters< T > ) => ReturnType< T > >(
	func: T
): T {
	const cache = new Map< string, ReturnType< T > >();

	const stringify = ( obj: unknown ): string => {
		try {
			return JSON.stringify( obj );
		} catch ( err ) {
			return String( obj );
		}
	};

	const memoized = ( ...args: Parameters< T > ): ReturnType< T > => {
		const key = Array.from( args ).map( stringify ).join( ',' );

		if ( cache.has( key ) ) {
			// TypeScript does not narrow based on the result of Map#has. Workarounds
			// are complex and require a type overload or predicate.
			// https://github.com/microsoft/TypeScript/issues/13086
			//
			// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
			return cache.get( key )!;
		}

		const result = func( ...args );
		cache.set( key, result );
		return result;
	};

	// Type assertion required to narrow type back to T.
	return memoized as T;
}
