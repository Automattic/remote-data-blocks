import { TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { DataSourceForm } from '@/data-sources/components/DataSourceForm';
import PasswordInputControl from '@/data-sources/components/PasswordInputControl';
import { useDataSources } from '@/data-sources/hooks/useDataSources';
import {
	SalesforceD2CConfig,
	SalesforceD2CServiceConfig,
	SettingsComponentProps,
} from '@/data-sources/types';
import { useForm } from '@/hooks/useForm';
import SalesforceCommerceD2CIcon from '@/settings/icons/SalesforceCommerceD2CIcon';

const SERVICE_CONFIG_VERSION = 1;

export const SalesforceD2CSettings = ( {
	mode,
	uuid,
	config,
}: SettingsComponentProps< SalesforceD2CConfig > ) => {
	const { onSave } = useDataSources< SalesforceD2CConfig >( false );

	const { state, handleOnChange, validState } = useForm< SalesforceD2CServiceConfig >( {
		initialValues: config?.service_config ?? {
			__version: SERVICE_CONFIG_VERSION,
			enable_blocks: true,
		},
	} );

	const shouldAllowSubmit =
		state.instance_url && state.store_id && state.client_id && state.client_secret;

	const onSaveClick = async () => {
		if ( ! validState ) {
			return;
		}

		const data: SalesforceD2CConfig = {
			service: 'salesforce-d2c',
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
					icon: SalesforceCommerceD2CIcon,
					width: '100px',
					height: '75px',
					verticalAlign: 'middle',
				} }
				inputIcon={ SalesforceCommerceD2CIcon }
				uuid={ uuid }
			>
				<TextControl
					type="text"
					label={ __( 'Instance URL', 'remote-data-blocks' ) }
					onChange={ instanceUrl => {
						handleOnChange( 'instance_url', instanceUrl ?? '' );
					} }
					value={ state.instance_url ?? '' }
					help={ __(
						'The instance URL. Example: https://scomhello123usa456org.my.salesforce.com'
					) }
					autoComplete="off"
					__next40pxDefaultSize
				/>

				<TextControl
					type="text"
					label={ __( 'Store ID', 'remote-data-blocks' ) }
					onChange={ storeId => {
						handleOnChange( 'store_id', storeId ?? '' );
					} }
					value={ state.store_id ?? '' }
					help={ __( 'The store identifier. Example: 0YFWs0000010fRfURF' ) }
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
					onChange={ clientSecret => {
						handleOnChange( 'client_secret', clientSecret ?? '' );
					} }
					value={ state.client_secret }
				/>
			</DataSourceForm.Setup>
			<DataSourceForm.Blocks
				handleOnChange={ handleOnChange }
				hasEnabledBlocks={ state.enable_blocks ?? true }
			/>
		</DataSourceForm>
	);
};
