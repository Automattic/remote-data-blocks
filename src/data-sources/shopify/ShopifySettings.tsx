import {
	Button,
	ButtonGroup,
	Card,
	CardBody,
	CardHeader,
	TextControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import PasswordInputControl from '@/data-sources/components/PasswordInputControl';
import { SlugInput } from '@/data-sources/components/SlugInput';
import { useDataSources } from '@/data-sources/hooks/useDataSources';
import { useShopifyShopName } from '@/data-sources/shopify/shopify-api-hooks';
import { ShopifyConfig } from '@/data-sources/types';
import { useForm } from '@/hooks/useForm';
import { useSettingsContext } from '@/settings/hooks/useSettingsNav';

export interface ShopifySettingsProps {
	mode: 'add' | 'edit';
	uuid?: string;
	config?: ShopifyConfig;
}

export type ShopifyFormState = Omit< ShopifyConfig, 'service' | 'uuid' >;

const initialState: ShopifyFormState = {
	store_name: '',
	access_token: '',
	slug: '',
};

const getInitialStateFromConfig = ( config?: ShopifyConfig ): ShopifyFormState => {
	if ( ! config ) {
		return initialState;
	}
	return {
		store_name: config.store_name,
		access_token: config.access_token,
		slug: config.slug,
	};
};

export const ShopifySettings = ( { mode, uuid: uuidFromProps, config }: ShopifySettingsProps ) => {
	const { goToMainScreen } = useSettingsContext();
	const { updateDataSource, addDataSource } = useDataSources( false );

	const { state, handleOnChange } = useForm< ShopifyFormState >( {
		initialValues: getInitialStateFromConfig( config ),
	} );

	const { shopName, connectionMessage } = useShopifyShopName(
		state.store_name,
		state.access_token
	);

	const onSaveClick = async () => {
		const shopifyConfig: ShopifyConfig = {
			uuid: uuidFromProps ?? '',
			service: 'shopify',
			store_name: state.store_name,
			access_token: state.access_token,
			slug: state.slug,
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

	/**
	 * Handle the slug change. Only accepts valid slugs which only contain alphanumeric characters and dashes.
	 * @param slug The slug to set.
	 */
	const onSlugChange = ( slug: string | undefined ) => {
		handleOnChange( 'slug', slug ?? '' );
	};

	return (
		<Card className="add-update-data-source-card">
			<CardHeader>
				<h2>
					{ mode === 'add' ? __( 'Add Shopify Data Source' ) : __( 'Edit Shopify Data Source' ) }
				</h2>
			</CardHeader>
			<CardBody>
				<form>
					<div className="form-group">
						<SlugInput slug={ state.slug } onChange={ onSlugChange } uuid={ uuidFromProps } />
					</div>

					<div className="form-group">
						<TextControl
							type="url"
							label={ __( 'Store Slug', 'remote-data-blocks' ) }
							onChange={ store_name => {
								handleOnChange( 'store_name', store_name ?? '' );
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

					<div className="form-group">
						<ButtonGroup className="form-actions">
							<Button variant="primary" onClick={ () => void onSaveClick() }>
								{ __( 'Save', 'remote-data-blocks' ) }
							</Button>
							<Button variant="secondary" onClick={ goToMainScreen }>
								{ __( 'Cancel', 'remote-data-blocks' ) }
							</Button>
						</ButtonGroup>
					</div>
				</form>
			</CardBody>
		</Card>
	);
};
