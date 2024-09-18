import {
	getContext,
	getElement,
	useEffect,
	useInit,
	useState,
	store,
} from '@wordpress/interactivity';

store( 'remote-data-blocks/airtable-elden-ring-map', {
	callbacks: {
		runMap: () => {
			// eslint-disable-next-line react-hooks/rules-of-hooks
			const [ ref, setRef ] = useState( null );

			// eslint-disable-next-line react-hooks/rules-of-hooks
			useInit( () => {
				const { ref: _ref } = getElement();
				setRef( _ref );
			} );

			const context = getContext();

			// eslint-disable-next-line react-hooks/rules-of-hooks
			useEffect( () => {
				if ( ! ref ) {
					return;
				}
				const coordinates = context?.coordinates
					? [ ...context.coordinates ].map( proxyObj => ( { ...proxyObj } ) )
					: [];
				ref.innerText = JSON.stringify( coordinates, null, 2 );
			}, [ context, ref ] );
		},
	},
} );
