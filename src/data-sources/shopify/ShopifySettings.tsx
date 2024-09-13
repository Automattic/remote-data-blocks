import {
	Button,
	ButtonGroup,
	__experimentalHeading as Heading,
	Panel,
	PanelBody,
	PanelRow,
	TextControl,
} from '@wordpress/components';
import { InputChangeCallback } from '@wordpress/components/build-types/input-control/types';
import { __ } from '@wordpress/i18n';

import { useDataSources } from '@/data-sources/hooks/useDataSources';
import { useShopifyShopName } from '@/data-sources/shopify/shopify-api-hooks';
import { ShopifyConfig } from '@/data-sources/types';
import { useForm } from '@/hooks/useForm';
import PasswordInputControl from '@/settings/PasswordInputControl';
import { useSettingsContext } from '@/settings/hooks/useSettingsNav';

export interface ShopifySettingsProps {
	mode: 'add' | 'edit';
	uuid?: string;
	config?: ShopifyConfig;
}

export type ShopifyFormState = Omit< ShopifyConfig, 'service' | 'uuid' >;

const initialState: ShopifyFormState = {
	store: '',
	token: '',
	display_name: '',
};

const getInitialStateFromConfig = ( config?: ShopifyConfig ): ShopifyFormState => {
	if ( ! config ) {
		return initialState;
	}
	return {
		store: config.store,
		token: config.token,
		display_name: config.display_name,
	};
};

export const ShopifySettings = ( { mode, uuid: uuidFromProps, config }: ShopifySettingsProps ) => {
	const { goToMainScreen } = useSettingsContext();
	const { updateDataSource, addDataSource } = useDataSources( false );

	const { state, handleOnChange } = useForm< ShopifyFormState >( {
		initialValues: getInitialStateFromConfig( config ),
	} );

	const { connectionMessage } = useShopifyShopName( state.store, state.token );

	const onSaveClick = async () => {
		const shopifyConfig: ShopifyConfig = {
			uuid: uuidFromProps ?? '',
			service: 'shopify',
			store: state.store,
			token: state.token,
			display_name: state.display_name,
		};

		if ( mode === 'add' ) {
			await addDataSource( shopifyConfig );
		} else {
			await updateDataSource( shopifyConfig );
		}
		goToMainScreen();
	};

	const onDisplayNameChange = ( displayName: string ) => {
		handleOnChange( 'display_name', displayName );
	};

	const onStoreChange = ( store: string ) => {
		handleOnChange( 'store', store );
	};

	const onTokenInputChange: InputChangeCallback = ( token: string | undefined ) => {
		handleOnChange( 'token', token ?? '' );
	};

	return (
		<Panel>
			<PanelBody>
				<Heading>
					{ mode === 'add'
						? __( 'Add a new Shopify Data Source' )
						: __( 'Edit Shopify Data Source' ) }
				</Heading>
				<PanelRow>
					<TextControl
						label={ __( 'Display Name', 'remote-data-blocks' ) }
						value={ state.display_name }
						onChange={ onDisplayNameChange }
					/>
				</PanelRow>
				<PanelRow>
					<TextControl
						label={ __( 'Shopify Store Name', 'remote-data-blocks' ) }
						onChange={ onStoreChange }
						size={ 20 }
						value={ state.store }
						autoComplete="off"
					/>
				</PanelRow>
				<PanelRow>
					<PasswordInputControl
						label={ __( 'Shopify Access Token', 'remote-data-blocks' ) }
						onChange={ onTokenInputChange }
						// eslint-disable-next-line @typescript-eslint/no-unsafe-assignment, @typescript-eslint/no-explicit-any
						size={ 999 as any }
						value={ state.token }
					/>
				</PanelRow>
				<PanelRow>{ connectionMessage }</PanelRow>
			</PanelBody>
			<ButtonGroup className="settings-form-cta-button-group">
				<Button
					variant="primary"
					// eslint-disable-next-line @typescript-eslint/no-misused-promises
					onClick={ onSaveClick }
				>
					{ __( 'Save', 'remote-data-blocks' ) }
				</Button>
				<Button variant="secondary" onClick={ goToMainScreen }>
					{ __( 'Cancel', 'remote-data-blocks' ) }
				</Button>
			</ButtonGroup>
		</Panel>
	);
};
