import { Button, ButtonGroup, Card, CardHeader, CardBody } from '@wordpress/components';
import { useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { ApiAuthSettingsInput } from '@/data-sources/components/ApiAuthSettingsInput';
import { ApiUrlMethodSettingsInput } from '@/data-sources/components/ApiUrlMethodSettingsInput';
import { SlugInput } from '@/data-sources/components/SlugInput';
import { useDataSources } from '@/data-sources/hooks/useDataSources';
import { RestApiFormState } from '@/data-sources/rest-api/types';
import { RestApiConfig, ApiAuth, ApiAuthFormState } from '@/data-sources/types';
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

	const getAuthState = (): ApiAuthFormState => {
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

					<ApiUrlMethodSettingsInput
						url={ state.url }
						method={ state.method }
						onChange={ handleOnChange }
					/>

					<ApiAuthSettingsInput auth={ getAuthState() } onChange={ handleOnChange } />

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
