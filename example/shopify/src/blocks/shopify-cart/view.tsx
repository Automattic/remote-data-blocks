import { store, getContext } from '@wordpress/interactivity';

import { LOCALSTORAGE_KEY, PUBLIC_STORE_NAME } from './config/constants';
import { addToCart, createCart, removeFromCart } from './data/shopify-cart';

interface CartLine {
	lineId: string;
	quantity: number;
	title: string;
}

export interface ViewContext {
	title: string;
	variantId: string;
}

interface ViewInitialState {
	cart: Record< string, CartLine >; // A map of variant IDs to cart lines.
	cartId?: string;
	checkoutUrl?: string;
	detailsOpen: boolean;
}

export interface ViewState extends ViewInitialState {
	blockName?: string;
	cartArray: CartLine[];
	quantityInCart: number;
	totalItems: number;
	restUrl?: string;
}

interface ViewStore {
	actions: {
		addToCart: ( quantity?: number ) => Promise< void >;
		removeFromCart: ( quantity?: number ) => Promise< void >;
		toggleDetails: () => void;
	};
	state: ViewState;
}

const initialState: ViewInitialState = {
	cart: {},
	detailsOpen: false,
};

try {
	const storedState = window.localStorage.getItem( LOCALSTORAGE_KEY ) ?? '{}';
	Object.assign( initialState, JSON.parse( storedState ) );
} catch ( err ) {}

function updateLocalStorage( persistedState: ViewState ): void {
	const { cart, cartId, checkoutUrl } = persistedState;
	window.localStorage.setItem( LOCALSTORAGE_KEY, JSON.stringify( { cart, cartId, checkoutUrl } ) );
}

const { state } = store< ViewStore >( PUBLIC_STORE_NAME, {
	actions: {
		addToCart: async () => {
			const context = getContext< ViewContext >();

			// TODO: Implement logic to increase quantity of an existing cart line.
			if ( state.cart[ context.variantId ] ) {
				return;
			}

			if ( ! state.cartId ) {
				const { cartId, checkoutUrl } = await createCart( state );

				state.cartId = cartId;
				state.checkoutUrl = checkoutUrl;
			}

			state.cart[ context.variantId ] = {
				lineId: await addToCart( state, context, 1 ),
				quantity: 1,
				title: context.title,
			};

			updateLocalStorage( state );
		},
		removeFromCart: async () => {
			if ( ! state.cartId ) {
				return;
			}

			const context = getContext< ViewContext >();

			if ( ! state.cart[ context.variantId ] ) {
				return;
			}

			await removeFromCart( state, context );

			Object.assign( state.cart, { [ context.variantId ]: undefined } );
			updateLocalStorage( state );
		},
		toggleDetails: () => {
			state.detailsOpen = ! state.detailsOpen;
		},
	},
	state: {
		// It's important to enumerate all properties here instead of spreading
		// another object, so that they are captured by the object proxy wrapper.
		// Otherwise you will get an error.
		cart: initialState.cart,
		cartId: initialState.cartId,
		checkoutUrl: initialState.checkoutUrl,
		detailsOpen: initialState.detailsOpen,

		// We rely on the default values for blockName and restUrl, supplied by
		// wp_interactivity_state() in render.php.

		get cartArray(): CartLine[] {
			return Object.values( state.cart );
		},

		get quantityInCart(): number {
			const { variantId } = getContext< ViewContext >();

			return state.cart[ variantId ]?.quantity ?? 0;
		},

		get totalItems(): number {
			return Object.values( state.cart ).reduce( ( acc, { quantity } ) => acc + quantity, 0 );
		},
	},
} );
