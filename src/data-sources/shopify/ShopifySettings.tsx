import { TextControl } from '@wordpress/components';
import { useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { MOCK_SHOP_STORE } from '@/data-sources/api-clients/shopify';
import { DataSourceForm } from '@/data-sources/components/DataSourceForm';
import PasswordInputControl from '@/data-sources/components/PasswordInputControl';
import { ConfigSource } from '@/data-sources/constants';
import { useDataSources } from '@/data-sources/hooks/useDataSources';
import { useShopifyShopName } from '@/data-sources/hooks/useShopify';
import { SettingsComponentProps, ShopifyConfig, ShopifyServiceConfig } from '@/data-sources/types';
import { useForm } from '@/hooks/useForm';
import { ShopifyIcon, ShopifyIconWithText } from '@/settings/icons/ShopifyIcon';

const SERVICE_CONFIG_VERSION = 1;

export const ShopifySettings = ( {
	mode,
	uuid,
	config,
}: SettingsComponentProps< ShopifyConfig > ) => {
	const { onSave } = useDataSources< ShopifyConfig >( false );

	const { state, handleOnChange, validState } = useForm< ShopifyServiceConfig >( {
		initialValues: config?.service_config ?? {
			__version: SERVICE_CONFIG_VERSION,
			enable_blocks: true,
		},
	} );

	const { shopName, connectionMessage } = useShopifyShopName(
		state.store_name ?? '',
		state.access_token ?? ''
	);

	const shouldAllowSubmit = useMemo( () => {
		if ( MOCK_SHOP_STORE === state.store_name ) {
			return true;
		}

		return state.store_name && state.access_token;
	}, [ state.store_name, state.access_token ] );

	const onTokenInputChange = ( token: string | undefined ) => {
		handleOnChange( 'access_token', token ?? '' );
	};

	const onShopNameChange = ( shopNameInput: string | undefined ) => {
		if ( ! shopNameInput ) {
			handleOnChange( 'store_name', '' );
			return;
		}

		// Extract hostname
		const url = new URL( `https://${ shopNameInput.trim().replace( /^https?:\/\//, '' ) }` );

		handleOnChange( 'store_name', url.hostname );
	};

	const onSaveClick = async () => {
		if ( ! validState ) {
			return;
		}

		const data: ShopifyConfig = {
			service: 'shopify',
			service_config: validState,
			uuid: uuid ?? null,
			config_source: ConfigSource.STORAGE,
		};

		return onSave( data, mode );
	};

	return (
		<DataSourceForm onSave={ onSaveClick }>
			<DataSourceForm.Setup
				canProceed={ Boolean( shouldAllowSubmit ) }
				displayName={ state.display_name ?? '' }
				handleOnChange={ handleOnChange }
				heading={ { icon: ShopifyIconWithText, width: '102px', height: '32px' } }
				inputIcon={ ShopifyIcon }
				uuid={ uuid }
			>
				<TextControl
					type="url"
					label={ __( 'Store', 'remote-data-blocks' ) }
					onChange={ onShopNameChange }
					value={ state.store_name ?? '' }
					placeholder="your-store.myshopify.com"
					help={
						<>
							{ __( 'Example: ' ) }
							<strong>{ __( 'your-store' ) }</strong>
							{ __( '.myshopify.com' ) }
						</>
					}
					autoComplete="off"
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>
				<PasswordInputControl
					disabled={ MOCK_SHOP_STORE === state.store_name }
					label={ __( 'Access Token', 'remote-data-blocks' ) }
					onChange={ onTokenInputChange }
					placeholder={ MOCK_SHOP_STORE === state.store_name ? 'No token required' : undefined }
					value={ state.access_token }
					help={ connectionMessage }
				/>
				<TextControl
					label={ __( 'Store Name', 'remote-data-blocks' ) }
					placeholder={ __( 'Auto-filled on successful connection.', 'remote-data-blocks' ) }
					value={ shopName ?? '' }
					onChange={ () => {} }
					tabIndex={ -1 }
					readOnly
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>
			</DataSourceForm.Setup>
			<DataSourceForm.Blocks
				handleOnChange={ handleOnChange }
				hasEnabledBlocks={ Boolean( state.enable_blocks ) }
			/>
		</DataSourceForm>
	);
};
