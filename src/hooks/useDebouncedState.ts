import { useEffect, useState } from '@wordpress/element';

export function useDebouncedState< T >(
	callback: ( value: T ) => void,
	delayInMs: number,
	initialValue: T
): [ T, ( value: T ) => void ] {
	const [ value, setValue ] = useState< T >( initialValue );

	useEffect( () => {
		const timeoutId = setTimeout( () => {
			callback( value );
		}, delayInMs );

		return () => clearTimeout( timeoutId );
	}, [ callback, delayInMs, value ] );

	return [ value, setValue ];
}
