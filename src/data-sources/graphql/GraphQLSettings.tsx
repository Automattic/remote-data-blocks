import { Card, CardHeader, CardBody, TextareaControl } from '@wordpress/components';
import { useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { ApiAuthSettingsInput } from '@/data-sources/components/ApiAuthSettingsInput';
import { ApiUrlMethodSettingsInput } from '@/data-sources/components/ApiUrlMethodSettingsInput';
import { FormActionsInput } from '@/data-sources/components/FormActionsInput';
import { SlugInput } from '@/data-sources/components/SlugInput';
import { GraphQLFormState } from '@/data-sources/graphql/type';
import { useDataSources } from '@/data-sources/hooks/useDataSources';
import { initialState as initialRestState } from '@/data-sources/rest-api/RestApiSettings';
import {
	GraphQLConfig,
	ApiAuth,
	ApiAuthFormState,
	SettingsComponentProps,
} from '@/data-sources/types';
import { useForm } from '@/hooks/useForm';
import { useSettingsContext } from '@/settings/hooks/useSettingsNav';

const initialState: GraphQLFormState = {
	...initialRestState,
	method: 'POST',
	query: '',
};

const getInitialStateFromConfig = ( config?: GraphQLConfig ): GraphQLFormState => {
	if ( ! config ) {
		return initialState;
	}

	const initialStateFromConfig: GraphQLFormState = {
		slug: config.slug,
		method: config.method,
		url: config.url,
		query: config.query,
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

export const GraphQLSettings = ( {
	mode,
	uuid: uuidFromProps,
	config,
}: SettingsComponentProps< GraphQLConfig > ) => {
	const { goToMainScreen } = useSettingsContext();

	const { state, handleOnChange } = useForm< GraphQLFormState >( {
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
		if (
			! state.slug ||
			! state.query ||
			! state.method ||
			! state.authType ||
			! state.authValue
		) {
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
		state.query,
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

		const graphqlConfig: GraphQLConfig = {
			uuid: uuidFromProps ?? '',
			service: 'graphql',
			slug: state.slug,
			method: state.method,
			url: state.url,
			query: state.query,
			auth,
		};

		if ( mode === 'add' ) {
			await addDataSource( graphqlConfig );
		} else {
			await updateDataSource( graphqlConfig );
		}
		goToMainScreen();
	};

	const onQueryChange = ( nextValue: string ) => {
		handleOnChange( 'query', nextValue );
	};

	return (
		<Card className="add-update-data-source-card">
			<CardHeader>
				<h2>
					{ mode === 'add' ? __( 'Add GraphQL Data Source' ) : __( 'Edit GraphQL Data Source' ) }
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

					<div className="form-group code-input">
						<TextareaControl
							label={ __( 'Query', 'remote-data-blocks' ) }
							value={ state.query }
							onChange={ onQueryChange }
							help={ __( 'The GraphQL query to execute.', 'remote-data-blocks' ) }
							rows={ 14 }
						/>
					</div>

					<ApiAuthSettingsInput auth={ getAuthState() } onChange={ handleOnChange } />

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
