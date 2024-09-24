import { Card, CardBody, CardHeader, SelectControl } from '@wordpress/components';
import { InputChangeCallback } from '@wordpress/components/build-types/input-control/types';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { ChangeEvent } from 'react';

import {
	useAirtableApiBases,
	useAirtableApiUserId,
} from '@/data-sources/airtable/airtable-api-hooks';
import { AirtableFormState } from '@/data-sources/airtable/types';
import { DataSourceFormActions } from '@/data-sources/components/DataSourceFormActions';
import PasswordInputControl from '@/data-sources/components/PasswordInputControl';
import { SlugInput } from '@/data-sources/components/SlugInput';
import { useDataSources } from '@/data-sources/hooks/useDataSources';
import { AirtableConfig, SettingsComponentProps } from '@/data-sources/types';
import { getConnectionMessage } from '@/data-sources/utils';
import { useForm } from '@/hooks/useForm';
import { useSettingsContext } from '@/settings/hooks/useSettingsNav';
import { StringIdName } from '@/types/common';
import { SelectOption } from '@/types/input';

const initialState: AirtableFormState = {
	access_token: '',
	base: null,
	slug: '',
};

const getInitialStateFromConfig = ( config?: AirtableConfig ): AirtableFormState => {
	if ( ! config ) {
		return initialState;
	}
	return {
		access_token: config.access_token,
		base: config.base,
		slug: config.slug,
	};
};

const defaultSelectBaseOption: SelectOption = {
	disabled: true,
	label: __( 'Auto-filled on successful connection.', 'remote-data-blocks' ),
	value: '',
};

export const AirtableSettings = ( {
	mode,
	uuid: uuidFromProps,
	config,
}: SettingsComponentProps< AirtableConfig > ) => {
	const { goToMainScreen } = useSettingsContext();
	const { updateDataSource, addDataSource, slugConflicts, loadingSlugConflicts } =
		useDataSources( false );

	const { state, handleOnChange } = useForm< AirtableFormState >( {
		initialValues: getInitialStateFromConfig( config ),
	} );

	const [ baseOptions, setBaseOptions ] = useState< SelectOption[] >( [ defaultSelectBaseOption ] );

	const { fetchingUserId, userId, userIdError } = useAirtableApiUserId( state.access_token );
	const { bases, basesError, fetchingBases } = useAirtableApiBases(
		state.access_token,
		userId ?? ''
	);

	const onSaveClick = async () => {
		if ( ! state.base ) {
			// TODO: Error handling
			return;
		}

		const airtableConfig: AirtableConfig = {
			uuid: uuidFromProps ?? '',
			service: 'airtable',
			access_token: state.access_token,
			base: state.base,
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
		handleOnChange( 'access_token', token ?? '' );
	};

	const onSelectChange = (
		value: string,
		extra?: { event?: ChangeEvent< HTMLSelectElement > }
	) => {
		if ( extra?.event ) {
			const { id } = extra.event.target;
			let newValue: StringIdName | null = null;
			if ( id === 'base' ) {
				const selectedBase = bases?.find( base => base.id === value );
				newValue = { id: value, name: selectedBase?.name ?? '' };
			}
			handleOnChange( id, newValue );
		}
	};

	/**
	 * Handle the slug change. Only accepts valid slugs which only contain alphanumeric characters and dashes.
	 * @param slug The slug to set.
	 */
	const onSlugChange = ( slug: string | undefined ) => {
		handleOnChange( 'slug', slug ?? '' );
	};

	const connectionMessage = useMemo( () => {
		if ( fetchingUserId ) {
			return __( 'Validating connection...', 'remote-data-blocks' );
		} else if ( userIdError ) {
			return getConnectionMessage(
				'error',
				__( 'Connection failed. Please verify your access token.', 'remote-data-blocks' )
			);
		} else if ( userId ) {
			return getConnectionMessage(
				'success',
				__( 'Connection successful.', 'remote-data-blocks' )
			);
		}
		return (
			<span>
				{ __( 'Provide access token to connect your Airtable', 'remote-data-blocks' ) } (
				<a href="https://support.airtable.com/docs/creating-personal-access-tokens" target="_label">
					{ __( 'guide', 'remote-data-blocks' ) }
				</a>
				).
			</span>
		);
	}, [ fetchingUserId, userId, userIdError ] );

	const shouldAllowSubmit = useMemo( () => {
		return bases !== null && state.base && state.slug && ! loadingSlugConflicts && ! slugConflicts;
	}, [ bases, state.base, state.slug, loadingSlugConflicts, slugConflicts ] );

	const basesHelpText = useMemo( () => {
		if ( userId ) {
			if ( basesError ) {
				return __(
					'Failed to fetch bases. Please check that your access token has the `schema.bases:read` Scope.'
				);
			} else if ( fetchingBases ) {
				return __( 'Fetching bases...' );
			} else if ( bases?.length === 0 ) {
				return __( 'No bases found.' );
			}
		}

		return 'Select a base from which to fetch data.';
	}, [ bases, basesError, fetchingBases, state.base, userId ] );

	useEffect( () => {
		if ( ! bases?.length ) {
			return;
		}

		setBaseOptions( [
			{
				...defaultSelectBaseOption,
				label: __( 'Select a base', 'remote-data-blocks' ),
			},
			...( bases ?? [] ).map( ( { name, id } ) => ( { label: name, value: id } ) ),
		] );
	}, [ bases ] );

	return (
		<Card className="add-update-data-source-card">
			<CardHeader>
				<h2>
					{ mode === 'add' ? __( 'Add Airtable Data Source' ) : __( 'Edit Airtable Data Source' ) }
				</h2>
			</CardHeader>
			<CardBody>
				<form>
					<div className="form-group">
						<SlugInput slug={ state.slug } onChange={ onSlugChange } uuid={ uuidFromProps } />
					</div>

					<div className="form-group">
						<PasswordInputControl
							label={ __( 'Access Token', 'remote-data-blocks' ) }
							onChange={ onTokenInputChange }
							value={ state.access_token }
							help={ connectionMessage }
						/>
					</div>

					<div className="form-group">
						<SelectControl
							id="base"
							label={ __( 'Base', 'remote-data-blocks' ) }
							value={ state.base?.id ?? '' }
							onChange={ onSelectChange }
							options={ baseOptions }
							help={ basesHelpText }
							disabled={ fetchingBases || ! bases?.length }
							__next40pxDefaultSize
						/>
					</div>

					<DataSourceFormActions
						onSave={ onSaveClick }
						onCancel={ goToMainScreen }
						IsSaveDisabled={ ! shouldAllowSubmit }
					/>
				</form>
			</CardBody>
		</Card>
	);
};
