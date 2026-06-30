import { useDebounce } from '@wordpress/compose';
import { useCallback, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { ShopifyApi, MOCK_SHOP_STORE } from '@/data-sources/api-clients/shopify';
import { getConnectionMessage } from '@/data-sources/utils';
import { useQuery } from '@/hooks/useQuery';

export interface ShopifyConnection {
	shopName: string | null;
	connectionMessage: JSX.Element | null;
}

export const useShopifyShopName = ( store: string, token: string ): ShopifyConnection => {
	const [ connectionMessage, setConnectionMessage ] = useState< null | JSX.Element >( null );

	const queryFn = useCallback( async () => {
		if ( ! store ) {
			return null;
		}

		if ( ! token && store !== MOCK_SHOP_STORE ) {
			return null;
		}

		const api = new ShopifyApi( store, token );
		return await api.shopName();
	}, [ store, token ] );

	const {
		data: shopName,
		isLoading: fetchingShopName,
		error: shopNameError,
		refetch: fetchShopName,
	} = useQuery( queryFn, { manualFetchOnly: true } );

	const debouncedFetchShopName = useDebounce( fetchShopName, 500 );

	useEffect( debouncedFetchShopName, [ store, token, debouncedFetchShopName ] );

	useEffect( () => {
		if ( fetchingShopName ) {
			setConnectionMessage(
				getConnectionMessage( null, __( 'Validating connection...', 'remote-data-blocks' ) )
			);
		} else if ( shopNameError ) {
			setConnectionMessage(
				getConnectionMessage(
					'error',
					__(
						'Connection failed. Please verify the myshopify.com domain name and Storefront API access token.',
						'remote-data-blocks'
					)
				)
			);
		} else if ( shopName ) {
			setConnectionMessage(
				getConnectionMessage( 'success', __( 'Connection successful.', 'remote-data-blocks' ) )
			);
		} else {
			setConnectionMessage( null );
		}
	}, [ fetchingShopName, shopNameError, shopName ] );

	if ( ! connectionMessage ) {
		setConnectionMessage(
			<span>
				{ __(
					'Use a Storefront API access token with the unauthenticated_read_product_listings scope. ',
					'remote-data-blocks'
				) }
				<a href="https://shopify.dev/docs/storefronts/headless/building-with-the-storefront-api/getting-started">
					{ __( 'How do I create one?', 'remote-data-blocks' ) }
				</a>
			</span>
		);
	}

	return { shopName, connectionMessage };
};
