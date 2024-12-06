import { TextControl } from '@wordpress/components';
import { useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { DataSourceForm } from '../components/DataSourceForm';
import { HttpAuthSettingsInput } from '@/data-sources/components/HttpAuthSettingsInput';
import { useDataSources } from '@/data-sources/hooks/useDataSources';
import { HttpAuth, HttpAuthFormState, HttpFormState } from '@/data-sources/http/types';
import { HttpConfig, SettingsComponentProps } from '@/data-sources/types';
import { useForm } from '@/hooks/useForm';
import { useSettingsContext } from '@/settings/hooks/useSettingsNav';
import HttpIcon from '@/settings/icons/HttpIcon';

const initialState: HttpFormState = {
	display_name: '',
	url: '',
	authType: 'bearer',
	authValue: '',
	authKey: '',
	authAddTo: 'header',
};

const getInitialStateFromConfig = ( config?: HttpConfig ): HttpFormState => {
	if ( ! config ) {
		return initialState;
	}

	const initialStateFromConfig: HttpFormState = {
		display_name: config.display_name,
		url: config.url,
		authType: config.auth.type,
		authValue: config.auth.value,
		authKey: '',
		authAddTo: 'header',
	};

	if ( config.auth.type === 'api-key' ) {
		initialStateFromConfig.authKey = config.auth.key;
		initialStateFromConfig.authAddTo = config.auth.addTo;
	}

	return initialStateFromConfig;
};

export const HttpSettings = ( {
	mode,
	uuid: uuidFromProps,
	config,
}: SettingsComponentProps< HttpConfig > ) => {
	const { goToMainScreen } = useSettingsContext();

	const { state, handleOnChange } = useForm< HttpFormState >( {
		initialValues: getInitialStateFromConfig( config ),
	} );

	const { addDataSource, updateDataSource } = useDataSources( false );

	const [ newUUID, setNewUUID ] = useState< string | null >( uuidFromProps ?? null );

	const getAuthState = (): HttpAuthFormState => {
		return {
			authType: state.authType,
			authValue: state.authValue,
			authKey: state.authKey,
			authAddTo: state.authAddTo,
		};
	};

	const shouldAllowSubmit = useMemo( () => {
		if ( state.authType === 'api-key' ) {
			if ( ! state.authKey || ! state.authAddTo ) {
				return false;
			}
		}

		if ( state.authType === 'none' ) {
			return state.url;
		}

		return state.url && state.authType && state.authValue;
	}, [ state.url, state.authType, state.authValue, state.authKey, state.authAddTo ] );

	const onSaveClick = async () => {
		if ( ! shouldAllowSubmit ) {
			return;
		}

		let auth: HttpAuth;

		if ( state.authType === 'api-key' ) {
			auth = {
				type: 'api-key',
				value: state.authValue,
				key: state.authKey,
				addTo: state.authAddTo,
			};
		} else {
			auth = {
				type: state.authType,
				value: state.authValue,
			};
		}

		const httpConfig: HttpConfig = {
			display_name: state.display_name,
			uuid: uuidFromProps ?? '',
			newUUID: newUUID ?? '',
			service: 'generic-http',
			url: state.url,
			auth,
		};

		if ( mode === 'add' ) {
			await addDataSource( httpConfig );
		} else {
			await updateDataSource( httpConfig );
		}
		goToMainScreen();
	};

	return (
		<DataSourceForm onSave={ onSaveClick }>
			<DataSourceForm.Setup
				canProceed={ Boolean( shouldAllowSubmit ) }
				displayName={ state.display_name }
				handleOnChange={ handleOnChange }
				heading={ { label: __( 'Connect HTTP Data Source', 'remote-data-blocks' ) } }
				inputIcon={ HttpIcon }
				newUUID={ newUUID }
				setNewUUID={ setNewUUID }
				uuidFromProps={ uuidFromProps }
			>
				<TextControl
					type="url"
					id="url"
					label={ __( 'URL', 'remote-data-blocks' ) }
					value={ state.url }
					onChange={ value => handleOnChange( 'url', value ) }
					autoComplete="off"
					__next40pxDefaultSize
					help={ __( 'The URL for the HTTP endpoint.', 'remote-data-blocks' ) }
				/>

				<HttpAuthSettingsInput auth={ getAuthState() } onChange={ handleOnChange } />
			</DataSourceForm.Setup>
		</DataSourceForm>
	);
};
