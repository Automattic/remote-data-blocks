import { SelectControl, Spinner } from '@wordpress/components';
import { InputChangeCallback } from '@wordpress/components/build-types/input-control/types';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { ChangeEvent } from 'react';

import { CustomFormFieldToken } from '../components/CustomFormFieldToken';
import { SUPPORTED_AIRTABLE_TYPES } from '@/data-sources/airtable/constants';
import { AirtableFormState } from '@/data-sources/airtable/types';
import { getAirtableOutputQueryMappingValue } from '@/data-sources/airtable/utils';
import { DataSourceForm } from '@/data-sources/components/DataSourceForm';
import PasswordInputControl from '@/data-sources/components/PasswordInputControl';
import {
	useAirtableApiBases,
	useAirtableApiTables,
	useAirtableApiUserId,
} from '@/data-sources/hooks/useAirtable';
import { useDataSources } from '@/data-sources/hooks/useDataSources';
import {
	AirtableConfig,
	AirtableOutputQueryMappingValue,
	SettingsComponentProps,
} from '@/data-sources/types';
import { getConnectionMessage } from '@/data-sources/utils';
import { useForm } from '@/hooks/useForm';
import { useSettingsContext } from '@/settings/hooks/useSettingsNav';
import { AirtableIcon, AirtableIconWithText } from '@/settings/icons/AirtableIcon';
import { StringIdName } from '@/types/common';
import { SelectOption } from '@/types/input';

const initialState: AirtableFormState = {
	access_token: '',
	base: null,
	display_name: '',
	table: null,
	table_fields: new Set< string >(),
};

const getInitialStateFromConfig = ( config?: AirtableConfig ): AirtableFormState => {
	if ( ! config ) {
		return initialState;
	}
	const initialStateFromConfig: AirtableFormState = {
		access_token: config.access_token,
		base: config.base,
		display_name: config.display_name,
		table: null,
		table_fields: new Set< string >(),
	};

	if ( Array.isArray( config.tables ) ) {
		const [ table ] = config.tables;

		if ( table ) {
			initialStateFromConfig.table = {
				id: table.id,
				name: table.name,
			};
			initialStateFromConfig.table_fields = new Set(
				table.output_query_mappings.map( ( { key } ) => key )
			);
		}
	}

	return initialStateFromConfig;
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
	const { updateDataSource, addDataSource } = useDataSources( false );

	const { state, handleOnChange } = useForm< AirtableFormState >( {
		initialValues: getInitialStateFromConfig( config ),
	} );

	const [ baseOptions, setBaseOptions ] = useState< SelectOption[] >( [ defaultSelectBaseOption ] );
	const [ tableOptions, setTableOptions ] = useState< SelectOption[] >( [
		defaultSelectTableOption,
	] );
	const [ availableTableFields, setAvailableTableFields ] = useState< string[] >( [] );
	const { fetchingUserId, userId, userIdError } = useAirtableApiUserId( state.access_token );
	const { bases, basesError, fetchingBases } = useAirtableApiBases(
		state.access_token,
		userId ?? ''
	);
	const { fetchingTables, tables, tablesError } = useAirtableApiTables(
		state.access_token,
		state.base?.id ?? ''
	);

	const [ newUUID, setNewUUID ] = useState< string | null >( uuidFromProps ?? null );

	const onSaveClick = async () => {
		if ( ! state.base || ! state.table ) {
			// TODO: Error handling
			return;
		}

		const selectedTable = tables?.find( table => table.id === state.table?.id );
		if ( ! selectedTable ) {
			return;
		}

		const airtableConfig: AirtableConfig = {
			uuid: uuidFromProps ?? '',
			newUUID: newUUID ?? '',
			service: 'airtable',
			access_token: state.access_token,
			base: state.base,
			display_name: state.display_name,
			tables: [
				{
					id: state.table.id,
					name: state.table.name,
					output_query_mappings: Array.from( state.table_fields )
						.map( key => {
							const field = selectedTable.fields.find( tableField => tableField.name === key );
							if ( field ) {
								return getAirtableOutputQueryMappingValue( field );
							}
							/**
							 * Remove any fields which are not from this table or not supported.
							 */
							return null;
						} )
						.filter( Boolean ) as AirtableOutputQueryMappingValue[],
				},
			],
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
			} else if ( id === 'table' ) {
				const selectedTable = tables?.find( table => table.id === value );
				newValue = { id: value, name: selectedTable?.name ?? '' };

				if ( value !== state.table?.id ) {
					// Reset the selected fields when the table changes.
					handleOnChange( 'table_fields', new Set< string >() );
				}
			}
			handleOnChange( id, newValue );
		}
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
				<a href="https://support.airtable.com/docs/creating-personal-access-tokens" target="_label">
					{ __( 'How do I get my token?', 'remote-data-blocks' ) }
				</a>
			</span>
		);
	}, [ fetchingUserId, userId, userIdError ] );

	const shouldAllowContinue = useMemo( () => {
		return userId !== null;
	}, [ userId ] );

	const shouldAllowSubmit = useMemo( () => {
		return bases !== null && tables !== null && Boolean( state.base ) && Boolean( state.table );
	}, [ bases, tables, state.base, state.table ] );

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
	}, [ bases, basesError, fetchingBases, userId ] );

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
							__( '%s fields found', 'remote-data-blocks' ),
							selectedTable.fields.length
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

	useEffect( () => {
		const newAvailableTableFields: string[] = [];

		if ( state.table && tables ) {
			const selectedTable = tables.find( table => table.id === state.table?.id );

			if ( selectedTable ) {
				selectedTable.fields.forEach( field => {
					if ( SUPPORTED_AIRTABLE_TYPES.includes( field.type ) ) {
						newAvailableTableFields.push( field.name );
					}
				} );
			}
		}

		setAvailableTableFields( newAvailableTableFields );
	}, [ state.table, tables ] );

	return (
		<>
			<DataSourceForm onSave={ onSaveClick }>
				<DataSourceForm.Setup
					displayName={ state.display_name }
					handleOnChange={ handleOnChange }
					heading={ { icon: AirtableIconWithText, width: '113.81px', height: '25px' } }
					inputIcon={ AirtableIcon }
					newUUID={ newUUID }
					setNewUUID={ setNewUUID }
					uuidFromProps={ uuidFromProps }
					canProceed={ shouldAllowContinue }
				>
					<PasswordInputControl
						label={ __( 'Access Token', 'remote-data-blocks' ) }
						onChange={ onTokenInputChange }
						value={ state.access_token }
						help={ connectionMessage }
					/>
				</DataSourceForm.Setup>
				<DataSourceForm.Scope canProceed={ shouldAllowSubmit }>
					<SelectControl
						id="base"
						label={ __( 'Base', 'remote-data-blocks' ) }
						value={ state.base?.id ?? '' }
						onChange={ onSelectChange }
						options={ baseOptions }
						help={ basesHelpText }
						disabled={ fetchingBases || ! bases?.length }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<SelectControl
						id="table"
						label={ __( 'Table', 'remote-data-blocks' ) }
						value={ state.table?.id ?? '' }
						onChange={ onSelectChange }
						options={ tableOptions }
						help={ tablesHelpText }
						disabled={ fetchingTables || ! tables?.length }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>

					{ state.table && availableTableFields.length ? (
						<CustomFormFieldToken
							label={ __( 'Fields', 'remote-data-blocks' ) }
							onChange={ selection => {
								if ( selection.includes( 'Select All' ) ) {
									handleOnChange( 'table_fields', new Set( availableTableFields ) );
								} else if ( selection.includes( 'Deselect All' ) ) {
									handleOnChange( 'table_fields', new Set() );
								} else {
									handleOnChange(
										'table_fields',
										new Set(
											selection.filter( item => item !== 'Select All' && item !== 'Deselect All' )
										)
									);
								}
							} }
							suggestions={ [
								...( state.table_fields.size === availableTableFields.length
									? [ 'Deselect All' ]
									: [ 'Select All' ] ),
								...availableTableFields,
							] }
							value={ Array.from( state.table_fields ) }
							__experimentalValidateInput={ input =>
								availableTableFields.includes( input ) ||
								input === 'Select All' ||
								input === 'Deselect All'
							}
							__nextHasNoMarginBottom
							__experimentalExpandOnFocus
							__next40pxDefaultSize
						/>
					) : (
						state.table && <Spinner />
					) }
				</DataSourceForm.Scope>
			</DataSourceForm>
		</>
	);
};
