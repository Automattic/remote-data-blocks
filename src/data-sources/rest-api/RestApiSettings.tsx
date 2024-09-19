import {
	Button,
	ButtonGroup,
	SelectControl,
	TextControl,
	Card,
	CardHeader,
	CardBody,
} from '@wordpress/components';
import { useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { ChangeEvent } from 'react';

import PasswordInputControl from '@/data-sources/components/PasswordInputControl';
import { SlugInput } from '@/data-sources/components/SlugInput';
import {
	REST_API_SOURCE_AUTH_TYPE_SELECT_OPTIONS,
	REST_API_SOURCE_ADD_TO_SELECT_OPTIONS,
	REST_API_SOURCE_METHOD_SELECT_OPTIONS,
} from '@/data-sources/constants';
import { useDataSources } from '@/data-sources/hooks/useDataSources';
import { RestApiFormState } from '@/data-sources/rest-api/types';
import { RestApiConfig, ApiAuth } from '@/data-sources/types';
import { useForm } from '@/hooks/useForm';
import { useSettingsContext } from '@/settings/hooks/useSettingsNav';

interface RestApiSettingsProps {
	mode: 'add' | 'edit';
	uuid?: string;
	config?: RestApiConfig;
}

const initialState: RestApiFormState = {
	slug: '',
	method: 'GET',
	url: '',
	authType: 'bearer',
	authValue: '',
	authKey: '',
	authAddTo: 'header',
};

const getInitialStateFromConfig = ( config?: RestApiConfig ): RestApiFormState => {
	if ( ! config ) {
		return initialState;
	}

	const initialStateFromConfig: RestApiFormState = {
		slug: config.slug,
		method: config.method,
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

export const RestApiSettings = ( { mode, uuid: uuidFromProps, config }: RestApiSettingsProps ) => {
	const { goToMainScreen } = useSettingsContext();

	const { state, handleOnChange } = useForm< RestApiFormState >( {
		initialValues: getInitialStateFromConfig( config ),
	} );

	const { addDataSource, updateDataSource } = useDataSources( false );

	/**
	 * Handle the slug change. Only accepts valid slugs which only contain alphanumeric characters and dashes.
	 * @param slug The slug to set.
	 */
	const onSlugChange = ( slug: string | undefined ) => {
		handleOnChange( 'slug', slug ?? '' );
	};

	const onSelectChange = (
		value: string,
		extra?: { event?: ChangeEvent< HTMLSelectElement > }
	) => {
		if ( extra?.event ) {
			const { id } = extra.event.target;
			handleOnChange( id, value );
		}
	};

	const shouldAllowSubmit = useMemo( () => {
		if ( ! state.slug || ! state.url || ! state.method || ! state.authType || ! state.authValue ) {
			return false;
		}

		if ( state.authType === 'api-key' ) {
			if ( ! state.authKey || ! state.authAddTo ) {
				return false;
			}
		}

		return true;
	}, [
		state.slug,
		state.url,
		state.method,
		state.authType,
		state.authValue,
		state.authKey,
		state.authAddTo,
	] );

	const onSaveClick = async () => {
		if ( ! shouldAllowSubmit ) {
			return;
		}

		let auth: ApiAuth;

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

		const restApiConfig: RestApiConfig = {
			uuid: uuidFromProps ?? '',
			service: 'rest-api',
			slug: state.slug,
			method: state.method,
			url: state.url,
			auth,
		};

		if ( mode === 'add' ) {
			await addDataSource( restApiConfig );
		} else {
			await updateDataSource( restApiConfig );
		}
		goToMainScreen();
	};

	return (
		<Card className="add-update-data-source-card">
			<CardHeader>
				<h2>
					{ mode === 'add' ? __( 'Add REST API Data Source' ) : __( 'Edit REST API Data Source' ) }
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
							id="url"
							label={ __( 'URL', 'remote-data-blocks' ) }
							value={ state.url }
							onChange={ value => handleOnChange( 'url', value ) }
							autoComplete="off"
							__next40pxDefaultSize
							help={ __( 'The URL for the REST API endpoint.', 'remote-data-blocks' ) }
						/>
					</div>

					<div className="form-group">
						<SelectControl
							id="method"
							label={ __( 'Method', 'remote-data-blocks' ) }
							value={ state.method }
							onChange={ onSelectChange }
							options={ REST_API_SOURCE_METHOD_SELECT_OPTIONS }
						/>
					</div>

					<div className="form-group">
						<SelectControl
							id="authType"
							label={ __( 'Authentication Type', 'remote-data-blocks' ) }
							value={ state.authType }
							onChange={ onSelectChange }
							options={ REST_API_SOURCE_AUTH_TYPE_SELECT_OPTIONS }
						/>
					</div>

					<div className="form-group">
						<PasswordInputControl
							id="authValue"
							label={ __( 'Authentication Value', 'remote-data-blocks' ) }
							value={ state.authValue }
							onChange={ value => handleOnChange( 'authValue', value ) }
							__next40pxDefaultSize
							help={ __(
								'The authentication value to use for the REST API.',
								'remote-data-blocks'
							) }
						/>
					</div>
					{ state.authType === 'api-key' && (
						<>
							<div className="form-group">
								<TextControl
									id="authKey"
									label={ __( 'Authentication Key Name', 'remote-data-blocks' ) }
									value={ state.authKey }
									onChange={ value => handleOnChange( 'authKey', value ) }
									__next40pxDefaultSize
								/>
							</div>
							<div className="form-group">
								<SelectControl
									id="authAddTo"
									label={ __( 'Add API Key to', 'remote-data-blocks' ) }
									value={ state.authAddTo }
									onChange={ onSelectChange }
									options={ REST_API_SOURCE_ADD_TO_SELECT_OPTIONS }
									help={ __(
										'Where to add the API key to. Authentication Key Name would be the header name or query param name and Authentication Value would be the value of the header or query param.',
										'remote-data-blocks'
									) }
								/>
							</div>
						</>
					) }

					<div className="form-group">
						<ButtonGroup className="form-actions">
							<Button
								variant="primary"
								onClick={ () => void onSaveClick() }
								disabled={ ! shouldAllowSubmit }
							>
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
