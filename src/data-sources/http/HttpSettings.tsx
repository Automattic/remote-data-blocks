import { Card, CardHeader, CardBody, TextControl } from '@wordpress/components';
import { useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { FormActionsInput } from '@/data-sources/components/FormActionsInput';
import { HttpAuthSettingsInput } from '@/data-sources/components/HttpAuthSettingsInput';
import { SlugInput } from '@/data-sources/components/SlugInput';
import { useDataSources } from '@/data-sources/hooks/useDataSources';
import { HttpFormState } from '@/data-sources/http/types';
import {
	HttpConfig,
	HttpAuth,
	HttpAuthFormState,
	SettingsComponentProps,
} from '@/data-sources/types';
import { useForm } from '@/hooks/useForm';
import { useSettingsContext } from '@/settings/hooks/useSettingsNav';

const initialState: HttpFormState = {
	slug: '',
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
		slug: config.slug,
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

	const getAuthState = (): HttpAuthFormState => {
		return {
			authType: state.authType,
			authValue: state.authValue,
			authKey: state.authKey,
			authAddTo: state.authAddTo,
		};
	};

	/**
	 * Handle the slug change. Only accepts valid slugs which only contain alphanumeric characters and dashes.
	 * @param slug The slug to set.
	 */
	const onSlugChange = ( slug: string | undefined ) => {
		handleOnChange( 'slug', slug ?? '' );
	};

	const shouldAllowSubmit = useMemo( () => {
		if ( ! state.slug || ! state.url || ! state.authType || ! state.authValue ) {
			return false;
		}

		if ( state.authType === 'api-key' ) {
			if ( ! state.authKey || ! state.authAddTo ) {
				return false;
			}
		}

		return true;
	}, [ state.slug, state.url, state.authType, state.authValue, state.authKey, state.authAddTo ] );

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
			uuid: uuidFromProps ?? '',
			service: 'http',
			slug: state.slug,
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
		<Card className="add-update-data-source-card">
			<CardHeader>
				<h2>{ mode === 'add' ? __( 'Add HTTP Data Source' ) : __( 'Edit HTTP Data Source' ) }</h2>
			</CardHeader>
			<CardBody>
				<form>
					<div className="form-group">
						<SlugInput slug={ state.slug } onChange={ onSlugChange } uuid={ uuidFromProps } />
					</div>

					<div className="form-group">
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
					</div>

					<HttpAuthSettingsInput auth={ getAuthState() } onChange={ handleOnChange } />

					<FormActionsInput
						onSave={ onSaveClick }
						onCancel={ goToMainScreen }
						saveDisabled={ ! shouldAllowSubmit }
					/>
				</form>
			</CardBody>
		</Card>
	);
};
