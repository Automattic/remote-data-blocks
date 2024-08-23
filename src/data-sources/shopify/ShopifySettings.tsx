import {
	Button,
	ButtonGroup,
	__experimentalHeading as Heading,
	TextControl,
	Panel,
	PanelBody,
	PanelRow,
} from '@wordpress/components';
import { InputChangeCallback } from '@wordpress/components/build-types/input-control/types';
import { __ } from '@wordpress/i18n';

import { useShopifyShopName } from './shopify-api-hooks';
import { useForm } from '../../hooks/useForm';
import PasswordInputControl from '../../settings/PasswordInputControl';
import { useSettingsContext } from '../../settings/hooks/useSettingsNav';
import { SlugInput } from '../SlugInput';
import { useDataSources } from '../hooks/useDataSources';
import { ShopifyConfig } from '../types';

export interface ShopifySettingsProps {
	mode: 'add' | 'edit';
	uuid?: string;
	config?: ShopifyConfig;
}

export type ShopifyFormState = Omit< ShopifyConfig, 'service' | 'uuid' >;

const initialState: ShopifyFormState = {
	store: '',
	token: '',
	slug: '',
};

const getInitialStateFromConfig = ( config?: ShopifyConfig ): ShopifyFormState => {
	if ( ! config ) {
		return initialState;
	}
	return {
		store: config.store,
		token: config.token,
		slug: config.slug,
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
		const airtableConfig: ShopifyConfig = {
			uuid: uuidFromProps ?? '',
			service: 'shopify',
			store: state.store,
			token: state.token,
			slug: state.slug,
		};

		if ( mode === 'add' ) {
			await addDataSource( airtableConfig );
		} else {
			await updateDataSource( airtableConfig );
		}
		goToMainScreen();
	};

	const onTokenInputChange: InputChangeCallback = ( token: string | undefined ) => {
		handleOnChange( 'token', token ?? '' );
	};

	/**
	 * Handle the slug change. Only accepts valid slugs which only contain alphanumeric characters and dashes.
	 * @param slug The slug to set.
	 */
	const onSlugChange = ( slug: string | undefined ) => {
		handleOnChange( 'slug', slug ?? '' );
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
					<SlugInput slug={ state.slug } onChange={ onSlugChange } uuid={ uuidFromProps } />
				</PanelRow>
				<PanelRow>
					<TextControl
						label={ __( 'Shopify Store Name', 'remote-data-blocks' ) }
						onChange={ store => {
							handleOnChange( 'store', store ?? '' );
						} }
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
					{ __( 'Cancel', 'vip-cb' ) }
				</Button>
			</ButtonGroup>
		</Panel>
	);
};
