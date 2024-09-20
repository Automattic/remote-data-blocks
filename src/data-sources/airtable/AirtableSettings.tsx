import {
	Button,
	ButtonGroup,
	SelectControl,
	Card,
	CardHeader,
	CardBody,
} from '@wordpress/components';
import { InputChangeCallback } from '@wordpress/components/build-types/input-control/types';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { ChangeEvent } from 'react';

import {
	useAirtableApiBases,
	useAirtableApiTables,
	useAirtableApiUserId,
} from '@/data-sources/airtable/airtable-api-hooks';
import { AirtableFormState } from '@/data-sources/airtable/types';
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
	token: '',
	base: null,
	table: null,
	slug: '',
};

const getInitialStateFromConfig = ( config?: AirtableConfig ): AirtableFormState => {
	if ( ! config ) {
		return initialState;
	}
	return {
		token: config.token,
		base: config.base,
		table: config.table,
		slug: config.slug,
	};
};

const defaultSelectBaseOption: SelectOption = {
	disabled: true,
	label: __( 'Auto-filled on successful connection.', 'remote-data-blocks' ),
	value: '',
};

const defaultSelectTableOption: SelectOption = {
	disabled: true,
	label: __( 'Auto-filled on valid base.', 'remote-data-blocks' ),
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
	const [ tableOptions, setTableOptions ] = useState< SelectOption[] >( [
		defaultSelectTableOption,
	] );

	const { fetchingUserId, userId, userIdError } = useAirtableApiUserId( state.token );
	const { bases, basesError, fetchingBases } = useAirtableApiBases( state.token, userId ?? '' );
	const { fetchingTables, tables, tablesError } = useAirtableApiTables(
		state.token,
		state.base?.id ?? ''
	);

	const handleSaveError = ( error: unknown ) => {
		console.error( error );
	};

	const onSaveClick = () => {
		if ( ! state.base || ! state.table ) {
			// TODO: Error handling
			return;
		}

		const airtableConfig: AirtableConfig = {
			uuid: uuidFromProps ?? '',
			service: 'airtable',
			token: state.token,
			base: state.base,
			table: state.table,
			slug: state.slug,
		};

		if ( mode === 'add' ) {
			void addDataSource( airtableConfig ).then( goToMainScreen ).catch( handleSaveError );
		}
		void updateDataSource( airtableConfig ).then( goToMainScreen ).catch( handleSaveError );
	};

	const onTokenInputChange: InputChangeCallback = ( token: string | undefined ) => {
		handleOnChange( 'token', token ?? '' );
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
			} else if ( id === 'table' ) {
				const selectedTable = tables?.find( table => table.id === value );
				newValue = { id: value, name: selectedTable?.name ?? '' };
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
		return (
			bases === null ||
			tables === null ||
			! state.base ||
			! state.table ||
			! state.slug ||
			loadingSlugConflicts ||
			slugConflicts
		);
	}, [ bases, tables, state.base, state.table, state.slug, loadingSlugConflicts, slugConflicts ] );

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

	const tablesHelpText = useMemo( () => {
		if ( bases?.length && state.base ) {
			if ( tablesError ) {
				return __(
					'Failed to fetch tables. Please check that your access token has the `schema.tables:read` Scope.',
					'remote-data-blocks'
				);
			} else if ( fetchingTables ) {
				return __( 'Fetching tables...', 'remote-data-blocks' );
			} else if ( tables ) {
				if ( state.table ) {
					const selectedTable = tables.find( table => table.id === state.table?.id );

					if ( selectedTable ) {
						return sprintf(
							__( 'Fields: %s', 'remote-data-blocks' ),
							selectedTable.fields.map( field => field.name ).join( ', ' )
						);
					}
				}

				if ( ! tables.length ) {
					return __( 'No tables found', 'remote-data-blocks' );
				}
			}

			return __( 'Select a table from which to fetch data.', 'remote-data-blocks' );
		}

		return 'Select a table to attach with this data source.';
	}, [ bases, fetchingTables, state.base, state.table, tables, tablesError ] );

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

	useEffect( () => {
		if ( ! state?.base ) {
			return;
		}

		if ( tables ) {
			setTableOptions( [
				{
					...defaultSelectBaseOption,
					label: __( 'Select a table', 'remote-data-blocks' ),
				},
				...tables.map( ( { name, id } ) => ( { label: name, value: id, disabled: false } ) ),
			] );
		}
	}, [ state.base, tables ] );

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
							value={ state.token }
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

					<div className="form-group">
						<SelectControl
							id="table"
							label={ __( 'Table', 'remote-data-blocks' ) }
							value={ state.table?.id ?? '' }
							onChange={ onSelectChange }
							options={ tableOptions }
							help={ tablesHelpText }
							disabled={ fetchingTables || ! tables?.length }
							__next40pxDefaultSize
						/>
					</div>

					<div className="form-group">
						<ButtonGroup className="form-actions">
							<Button
								variant="primary"
								onClick={ () => void onSaveClick() }
								disabled={ shouldAllowSubmit }
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
