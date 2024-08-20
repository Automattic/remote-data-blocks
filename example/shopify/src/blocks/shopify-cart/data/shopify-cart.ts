import type { ViewContext, ViewState } from '../view';

async function apiFetch(
	url: string | null = null,
	requestBody: object
): Promise< RemoteDataApiResult[] > {
	if ( ! url ) {
		throw new Error( 'No REST API URL provided' );
	}

	const response = await window.fetch( url, {
		body: JSON.stringify( requestBody ),
		headers: {
			'Content-Type': 'application/json',
		},
		method: 'POST',
	} );
	const { body } = ( await response.json() ) as RemoteDataApiResponse;

	return body?.results ?? [];
}

export async function createCart(
	state: ViewState
): Promise< Pick< ViewState, 'cartId' | 'checkoutUrl' > > {
	const results = await apiFetch( state.restUrl, {
		block_name: state.blockName,
		query_key: '__CREATE_CART__',
		query_input: {},
	} );
	if ( ! results[ 0 ]?.result?.cart_id?.value || ! results[ 0 ]?.result?.checkout_url?.value ) {
		throw new Error( 'Failed to create cart: Invalid response from API' );
	}

	return {
		cartId: results[ 0 ].result.cart_id.value,
		checkoutUrl: results[ 0 ].result.checkout_url.value,
	};
}

export async function addToCart(
	state: ViewState,
	context: ViewContext,
	quantity: number
): Promise< string > {
	const results = await apiFetch( state.restUrl, {
		block_name: state.blockName,
		query_key: '__ADD_TO_CART__',
		query_input: { cart_id: state.cartId, variant_id: context.variantId, quantity },
	} );

	if ( ! results[ 0 ]?.result?.id?.value ) {
		throw new Error( 'Failed to add to cart: Invalid response from API' );
	}

	return results[ 0 ].result.id.value;
}

export async function removeFromCart( state: ViewState, context: ViewContext ): Promise< void > {
	const lineId = state.cart[ context.variantId ]?.lineId;

	if ( ! lineId ) {
		throw new Error( 'Failed to remove from cart: Line item not found' );
	}

	await apiFetch( state.restUrl, {
		block_name: state.blockName,
		query_key: '__REMOVE_FROM_CART__',
		query_input: { cart_id: state.cartId, line_id: lineId },
	} );
}
