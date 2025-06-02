import { __, sprintf } from '@wordpress/i18n';

export const MOCK_SHOP_STORE = 'mock.shop';

export class ShopifyApi {
	constructor( private store: string, private token?: string ) {
		if ( ! token && store !== MOCK_SHOP_STORE ) {
			throw new Error( 'Access token is required' );
		}
	}

	private getGraphqlEndpointUrl() {
		// Special case for the Shopify Mock Store, which uses a different endpoint.
		if ( MOCK_SHOP_STORE === this.store ) {
			return 'https://mock.shop/api';
		}

		if ( this.store.endsWith( '.myshopify.com' ) ) {
			// If the store already has the .myshopify.com domain, use it directly.
			return `https://${ this.store }/api/2024-07/graphql.json`;
		}

		return `https://${ this.store }.myshopify.com/api/2024-07/graphql.json`;
	}

	private getAuthHeaders(): Record< string, string > {
		if ( MOCK_SHOP_STORE === this.store ) {
			return {
				'Content-Type': 'application/json',
			};
		}

		if ( ! this.token ) {
			throw new Error( 'No token provided' );
		}

		return {
			'Content-Type': 'application/json',
			'X-Shopify-Storefront-Access-Token': this.token,
		};
	}

	private async query< T >( query: string, options: RequestInit = {} ): Promise< T > {
		const url = this.getGraphqlEndpointUrl();

		const response = await fetch( url, {
			...options,
			method: 'POST',
			headers: {
				...( options.headers ?? {} ),
				...this.getAuthHeaders(),
			},
			body: JSON.stringify( { query } ),
		} );

		if ( ! response.ok ) {
			const errorText = `${ response.status } - ${ await response.text() }`;
			throw new Error( `[Shopify API] ${ sprintf( __( 'Error: %s' ), errorText ) }` );
		}

		return response.json() as Promise< T >;
	}

	public async shopName(): Promise< string > {
		const result = await this.query< { data?: { shop?: { name: string } } } >(
			`query {
				shop {
					name
				}
			}`
		);
		return result.data?.shop?.name ?? '';
	}
}
