import { useDebounce } from '@wordpress/compose';
import { useCallback, useEffect, useMemo, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

import { ShopifyApi } from './shopify-api';
import { useQuery } from '../../hooks/useQuery';

export interface ShopifyConnection {
	shopName: string | null;
	connectionMessage: string;
}

export const useShopifyShopName = ( store: string, token: string ): ShopifyConnection => {
	const [ connectionMessage, setConnectionMessage ] = useState< string >( '' );

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
			setConnectionMessage( __( 'Checking connection...', 'remote-data-blocks' ) );
		} else if ( shopNameError ) {
			setConnectionMessage(
				__(
					'Connection failed. Please check your Store Name and Access Token.',
					'remote-data-blocks'
				)
			);
		} else if ( shopName ) {
			setConnectionMessage(
				__( sprintf( 'Connection successful. Shop Name: %s', shopName ), 'remote-data-blocks' )
			);
		} else {
			setConnectionMessage( '' );
		}
	}, [ fetchingShopName, shopNameError, shopName ] );

	return { shopName, connectionMessage };
};
