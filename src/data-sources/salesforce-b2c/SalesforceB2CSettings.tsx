import { TextControl } from '@wordpress/components';
import { useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { DataSourceForm } from '../components/DataSourceForm';
import PasswordInputControl from '@/data-sources/components/PasswordInputControl';
import { useDataSources } from '@/data-sources/hooks/useDataSources';
import {
	SettingsComponentProps,
	SalesforceB2CConfig,
	SalesforceB2CServiceConfig,
} from '@/data-sources/types';
import { useForm } from '@/hooks/useForm';
import SalesforceCommerceB2CIcon from '@/settings/icons/SalesforceCommerceB2CIcon';

const SERVICE_CONFIG_VERSION = 1;

export const SalesforceB2CSettings = ( {
	mode,
	uuid,
	config,
}: SettingsComponentProps< SalesforceB2CConfig > ) => {
	const { onSave } = useDataSources< SalesforceB2CConfig >( false );

	const { state, handleOnChange, validState } = useForm< SalesforceB2CServiceConfig >( {
		initialValues: config?.service_config ?? { __version: SERVICE_CONFIG_VERSION },
	} );

	const shouldAllowSubmit = useMemo( () => {
		return state.shortcode && state.organization_id && state.client_id && state.client_secret;
	}, [ state.shortcode, state.organization_id, state.client_id, state.client_secret ] );

	const onSaveClick = async () => {
		if ( ! validState ) {
			return;
		}

		const data: SalesforceB2CConfig = {
			service: 'salesforce-b2c',
			service_config: validState,
			uuid: uuid ?? null,
		};

		return onSave( data, mode );
	};

	return (
		<DataSourceForm onSave={ onSaveClick }>
			<DataSourceForm.Setup
				canProceed={ Boolean( shouldAllowSubmit ) }
				displayName={ state.display_name ?? '' }
				handleOnChange={ handleOnChange }
				heading={ {
					icon: SalesforceCommerceB2CIcon,
					width: '100px',
					height: '75px',
					verticalAlign: 'middle',
				} }
				inputIcon={ SalesforceCommerceB2CIcon }
			>
				<TextControl
					type="text"
					label={ __( 'Merchant shortCode', 'remote-data-blocks' ) }
					onChange={ shortCode => {
						handleOnChange( 'shortcode', shortCode ?? '' );
					} }
					value={ state.shortcode ?? '' }
					help={ __( 'The region-specific merchant identifier. Example: 0dnz6ope' ) }
					autoComplete="off"
					__next40pxDefaultSize
				/>

				<TextControl
					type="text"
					label={ __( 'Organization ID', 'remote-data-blocks' ) }
					onChange={ shortCode => {
						handleOnChange( 'organization_id', shortCode ?? '' );
					} }
					value={ state.organization_id ?? '' }
					help={ __( 'The organization ID. Example: f_ecom_mirl_012' ) }
					autoComplete="off"
					__next40pxDefaultSize
				/>

				<TextControl
					type="text"
					label={ __( 'Client ID', 'remote-data-blocks' ) }
					onChange={ shortCode => {
						handleOnChange( 'client_id', shortCode ?? '' );
					} }
					value={ state.client_id ?? '' }
					help={ __( 'Example: bc2991f1-eec8-4976-8774-935cbbe84f18' ) }
					autoComplete="off"
					__next40pxDefaultSize
				/>

				<PasswordInputControl
					label={ __( 'Client Secret', 'remote-data-blocks' ) }
					onChange={ shortCode => {
						handleOnChange( 'client_secret', shortCode ?? '' );
					} }
					value={ state.client_secret }
				/>
			</DataSourceForm.Setup>
		</DataSourceForm>
	);
};
