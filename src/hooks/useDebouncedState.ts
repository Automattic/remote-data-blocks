import { useCallback, useEffect, useRef, useState } from '@wordpress/element';

export function useDebouncedState< T >(
	delayInMs: number,
	initialValue: T
): [ T, ( value: T, immediate?: boolean ) => void ] {
	const timer = useRef< NodeJS.Timeout | null >( null );
	const [ value, setValue ] = useState< T >( initialValue );
	useEffect( () => () => clearTimeout( timer.current ?? undefined ), [] );

	const debouncedSetValue = useCallback(
		( newValue: T, immediate = false ) => {
			if ( timer.current ) {
				clearTimeout( timer.current );
			}

			if ( immediate ) {
				setValue( newValue );
				return;
			}

			timer.current = setTimeout( () => {
				setValue( newValue );
			}, delayInMs );
		},
		[ delayInMs ]
	);

	return [ value, debouncedSetValue ];
}
