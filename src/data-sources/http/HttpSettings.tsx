import { TextControl } from '@wordpress/components';
import { useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { DataSourceForm } from '../components/DataSourceForm';
import { HttpAuthSettingsInput } from '@/data-sources/components/HttpAuthSettingsInput';
import { useDataSources } from '@/data-sources/hooks/useDataSources';
import { HttpAuth } from '@/data-sources/http/types';
import { HttpConfig, HttpServiceConfig, SettingsComponentProps } from '@/data-sources/types';
import { useForm } from '@/hooks/useForm';
import HttpIcon from '@/settings/icons/HttpIcon';

const SERVICE_CONFIG_VERSION = 1;

export const HttpSettings = ( { mode, uuid, config }: SettingsComponentProps< HttpConfig > ) => {
	const { state, handleOnChange, validState } = useForm< HttpServiceConfig >( {
		initialValues: config?.service_config ?? { __version: SERVICE_CONFIG_VERSION },
	} );

	const { onSave } = useDataSources< HttpConfig >( false );

	const getAuthState = (): HttpServiceConfig[ 'auth' ] => {
		return state.auth;
	};

	const shouldAllowSubmit = useMemo( () => {
		if ( state.auth?.type === 'api-key' ) {
			return ! state.auth.key || ! state.auth.add_to;
		}

		if ( state.auth?.type === 'none' ) {
			return state.url;
		}

		return state.url && state.auth?.type && state.auth?.value;
	}, [ state.url, state.auth ] );

	const onSaveClick = async () => {
		if ( ! validState || ! shouldAllowSubmit ) {
			return;
		}

		let auth: HttpAuth;

		if ( state.auth?.type === 'api-key' ) {
			auth = {
				type: 'api-key',
				value: state.auth?.value,
				key: state.auth?.key,
				add_to: state.auth?.add_to,
			};
		} else {
			auth = {
				type: state.auth?.type ?? 'none',
				value: state.auth?.value ?? '',
			};
		}

		const httpConfig: HttpConfig = {
			service: 'generic-http',
			service_config: {
				...validState,
				auth,
			},
			uuid: uuid ?? null,
		};

		return onSave( httpConfig, mode );
	};

	return (
		<DataSourceForm onSave={ onSaveClick }>
			<DataSourceForm.Setup
				canProceed={ Boolean( shouldAllowSubmit ) }
				displayName={ state.display_name ?? '' }
				handleOnChange={ handleOnChange }
				heading={ { label: __( 'Connect HTTP Data Source', 'remote-data-blocks' ) } }
				inputIcon={ HttpIcon }
			>
				<TextControl
					type="url"
					id="url"
					label={ __( 'URL', 'remote-data-blocks' ) }
					value={ state.url ?? '' }
					onChange={ value => handleOnChange( 'url', value ) }
					autoComplete="off"
					__next40pxDefaultSize
					help={ __( 'The URL for the HTTP endpoint.', 'remote-data-blocks' ) }
					__nextHasNoMarginBottom
				/>

				<HttpAuthSettingsInput auth={ getAuthState() } onChange={ handleOnChange } />
			</DataSourceForm.Setup>
		</DataSourceForm>
	);
};
