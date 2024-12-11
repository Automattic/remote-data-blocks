import { TextControl } from '@wordpress/components';
import { useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { DataSourceForm } from '../components/DataSourceForm';
import { DataSourceFormActions } from '@/data-sources/components/DataSourceFormActions';
import PasswordInputControl from '@/data-sources/components/PasswordInputControl';
import { useDataSources } from '@/data-sources/hooks/useDataSources';
import { SettingsComponentProps, SalesforceB2CConfig } from '@/data-sources/types';
import { useForm } from '@/hooks/useForm';
import { useSettingsContext } from '@/settings/hooks/useSettingsNav';

export type SalesforceB2CFormState = Omit< SalesforceB2CConfig, 'service' | 'uuid' >;

const initialState: SalesforceB2CFormState = {
	display_name: '',
	shortcode: '',
	organization_id: '',
	client_id: '',
	client_secret: '',
};

const getInitialStateFromConfig = ( config?: SalesforceB2CConfig ): SalesforceB2CFormState => {
	if ( ! config ) {
		return initialState;
	}

	return {
		display_name: config.display_name,
		shortcode: config.shortcode,
		organization_id: config.organization_id,
		client_id: config.client_id,
		client_secret: config.client_secret,
	};
};

export const SalesforceB2CSettings = ( {
	mode,
	uuid: uuidFromProps,
	config,
}: SettingsComponentProps< SalesforceB2CConfig > ) => {
	const { goToMainScreen } = useSettingsContext();
	const { updateDataSource, addDataSource } = useDataSources( false );

	const [ newUUID, setNewUUID ] = useState< string | null >( uuidFromProps ?? null );

	const { state, handleOnChange } = useForm< SalesforceB2CFormState >( {
		initialValues: getInitialStateFromConfig( config ),
	} );

	const shouldAllowSubmit = useMemo( () => {
		return state.shortcode && state.organization_id && state.client_id && state.client_secret;
	}, [ state.shortcode, state.organization_id, state.client_id, state.client_secret ] );

	const onSaveClick = async () => {
		const salesforceConfig: SalesforceB2CConfig = {
			uuid: uuidFromProps ?? '',
			display_name: state.display_name,
			service: 'salesforce-b2c',
			shortcode: state.shortcode,
			organization_id: state.organization_id,
			client_id: state.client_id,
			client_secret: state.client_secret,
		};

		if ( mode === 'add' ) {
			await addDataSource( salesforceConfig );
		} else {
			await updateDataSource( salesforceConfig );
		}

		goToMainScreen();
	};

	return (
		<DataSourceForm
			displayName={ state.display_name }
			handleOnChange={ handleOnChange }
			mode={ mode }
			newUUID={ newUUID }
			setNewUUID={ setNewUUID }
			uuidFromProps={ uuidFromProps }
			heading={
				mode === 'add'
					? __( 'Add Salesforce B2C Data Source' )
					: __( 'Edit Salesforce B2C Data Source' )
			}
		>
			<div className="form-group">
				<TextControl
					type="text"
					label={ __( 'Merchant shortCode', 'remote-data-blocks' ) }
					onChange={ shortCode => {
						handleOnChange( 'shortcode', shortCode ?? '' );
					} }
					value={ state.shortcode }
					help={ __( 'The region-specific merchant identifier. Example: 0dnz6ope' ) }
					autoComplete="off"
					__next40pxDefaultSize
				/>
			</div>

			<div className="form-group">
				<TextControl
					type="text"
					label={ __( 'Organization ID', 'remote-data-blocks' ) }
					onChange={ shortCode => {
						handleOnChange( 'organization_id', shortCode ?? '' );
					} }
					value={ state.organization_id }
					help={ __( 'The organization ID. Example: f_ecom_mirl_012' ) }
					autoComplete="off"
					__next40pxDefaultSize
				/>
			</div>

			<div className="form-group">
				<TextControl
					type="text"
					label={ __( 'Client ID', 'remote-data-blocks' ) }
					onChange={ shortCode => {
						handleOnChange( 'client_id', shortCode ?? '' );
					} }
					value={ state.client_id }
					help={ __( 'Example: bc2991f1-eec8-4976-8774-935cbbe84f18' ) }
					autoComplete="off"
					__next40pxDefaultSize
				/>
			</div>

			<div className="form-group">
				<PasswordInputControl
					label={ __( 'Client Secret', 'remote-data-blocks' ) }
					onChange={ shortCode => {
						handleOnChange( 'client_secret', shortCode ?? '' );
					} }
					value={ state.client_secret }
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
