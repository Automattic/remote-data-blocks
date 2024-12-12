import { TextControl } from '@wordpress/components';
import { useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { DataSourceForm } from '../components/DataSourceForm';
import { DataSourceFormActions } from '@/data-sources/components/DataSourceFormActions';
import PasswordInputControl from '@/data-sources/components/PasswordInputControl';
import { useDataSources } from '@/data-sources/hooks/useDataSources';
import { useShopifyShopName } from '@/data-sources/hooks/useShopify';
import { SettingsComponentProps, ShopifyConfig } from '@/data-sources/types';
import { useForm } from '@/hooks/useForm';
import { useSettingsContext } from '@/settings/hooks/useSettingsNav';

export type ShopifyFormState = Omit< ShopifyConfig, 'service' | 'uuid' >;

const initialState: ShopifyFormState = {
	display_name: '',
	store_name: '',
	access_token: '',
};

const getInitialStateFromConfig = ( config?: ShopifyConfig ): ShopifyFormState => {
	if ( ! config ) {
		return initialState;
	}
	return {
		display_name: config.display_name,
		store_name: config.store_name,
		access_token: config.access_token,
	};
};

export const ShopifySettings = ( {
	mode,
	uuid: uuidFromProps,
	config,
}: SettingsComponentProps< ShopifyConfig > ) => {
	const { goToMainScreen } = useSettingsContext();
	const { updateDataSource, addDataSource } = useDataSources( false );

	const { state, handleOnChange } = useForm< ShopifyFormState >( {
		initialValues: getInitialStateFromConfig( config ),
	} );

	const { shopName, connectionMessage } = useShopifyShopName(
		state.store_name,
		state.access_token
	);

	const [ newUUID, setNewUUID ] = useState< string | null >( uuidFromProps ?? null );

	const shouldAllowSubmit = useMemo( () => {
		return state.store_name && state.access_token;
	}, [ state.store_name, state.access_token ] );

	const onSaveClick = async () => {
		const shopifyConfig: ShopifyConfig = {
			display_name: state.display_name,
			uuid: uuidFromProps ?? '',
			newUUID: newUUID ?? '',
			service: 'shopify',
			store_name: state.store_name,
			access_token: state.access_token,
		};

		if ( mode === 'add' ) {
			await addDataSource( shopifyConfig );
		} else {
			await updateDataSource( shopifyConfig );
		}
		goToMainScreen();
	};

	const onTokenInputChange = ( token: string | undefined ) => {
		handleOnChange( 'access_token', token ?? '' );
	};

	return (
		<DataSourceForm
			displayName={ state.display_name }
			handleOnChange={ handleOnChange }
			heading={
				mode === 'add' ? __( 'Add Shopify Data Source' ) : __( 'Edit Shopify Data Source' )
			}
			mode={ mode }
			newUUID={ newUUID }
			setNewUUID={ setNewUUID }
			uuidFromProps={ uuidFromProps }
		>
			<div className="form-group">
				<TextControl
					type="url"
					label={ __( 'Store Slug', 'remote-data-blocks' ) }
					onChange={ storeName => {
						handleOnChange( 'store_name', storeName ?? '' );
					} }
					value={ state.store_name }
					placeholder="your-shop-name"
					help={ __( 'Example: https://your-shop-name.myshopify.com', 'remote-data-blocks' ) }
					autoComplete="off"
					__next40pxDefaultSize
				/>
			</div>

			<div className="form-group">
				<PasswordInputControl
					label={ __( 'Access Token', 'remote-data-blocks' ) }
					onChange={ onTokenInputChange }
					value={ state.access_token }
					help={ connectionMessage }
				/>
			</div>

			<div className="form-group">
				<TextControl
					label={ __( 'Store Name', 'remote-data-blocks' ) }
					placeholder={ __( 'Auto-filled on successful connection.', 'remote-data-blocks' ) }
					value={ shopName ?? '' }
					onChange={ () => {} }
					tabIndex={ -1 }
					readOnly
					__next40pxDefaultSize
				/>
			</div>

			<DataSourceFormActions
				onSave={ onSaveClick }
				onCancel={ goToMainScreen }
				isSaveDisabled={ ! shouldAllowSubmit }
			/>
		</DataSourceForm>
	);
};
