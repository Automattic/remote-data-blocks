import { useDebounce } from '@wordpress/compose';
import { useCallback, useEffect, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { ShopifyApi } from '@/data-sources/shopify/shopify-api';
import { getConnectionMessage } from '@/data-sources/utils';
import { useQuery } from '@/hooks/useQuery';

export interface ShopifyConnection {
	shopName: string | null;
	connectionMessage: JSX.Element | null;
}

export const useShopifyShopName = ( store: string, token: string ): ShopifyConnection => {
	const [ connectionMessage, setConnectionMessage ] = useState< null | JSX.Element >( null );

	const api = useMemo( () => new ShopifyApi( store, token ), [ store, token ] );
	const queryFn = useCallback( async () => {
		if ( ! ( store && token ) ) {
			return null;
		}
		return api.shopName();
	}, [ api, store, token ] );

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
						'Connection failed. Please verify store slug and access token.',
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
				{ __( 'Provide access token to connect your Shopify store', 'remote-data-blocks' ) } (
				<a href="https://shopify.dev/docs/apps/build/authentication-authorization/access-tokens/generate-app-access-tokens-admin">
					{ __( 'guide', 'remote-data-blocks' ) }
				</a>
				).
			</span>
		);
	}

	return { shopName, connectionMessage };
};
