import { SelectControl, Spinner } from '@wordpress/components';
import { InputChangeCallback } from '@wordpress/components/build-types/input-control/types';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { ChangeEvent } from 'react';

import { CustomFormFieldToken } from '../components/CustomFormFieldToken';
import { SUPPORTED_AIRTABLE_TYPES } from '@/data-sources/airtable/constants';
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
	AirtableServiceConfig,
	SettingsComponentProps,
} from '@/data-sources/types';
import { getConnectionMessage } from '@/data-sources/utils';
import { useForm } from '@/hooks/useForm';
import { AirtableIcon, AirtableIconWithText } from '@/settings/icons/AirtableIcon';
import { SelectOption } from '@/types/input';

const SERVICE_CONFIG_VERSION = 1;

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

// eslint-disable-next-line complexity
export const AirtableSettings = ( {
	mode,
	uuid,
	config,
}: SettingsComponentProps< AirtableConfig > ) => {
	const { onSave } = useDataSources< AirtableConfig >( false );

	const { state, handleOnChange, validState } = useForm< AirtableServiceConfig >( {
		initialValues: config?.service_config ?? { __version: SERVICE_CONFIG_VERSION },
	} );

	const [ currentTableId, setCurrentTableId ] = useState< string | null >(
		state.tables?.[ 0 ]?.id ?? null
	);
	const [ tableFields, setTableFields ] = useState< string[] >(
		state.tables?.[ 0 ]?.output_query_mappings.map( mapping => mapping.key ).filter( Boolean ) ?? []
	);
	const { fetchingUserId, userId, userIdError } = useAirtableApiUserId( state.access_token ?? '' );
	const { bases, basesError, fetchingBases } = useAirtableApiBases(
		state.access_token ?? '',
		userId ?? ''
	);
	const { fetchingTables, tables, tablesError } = useAirtableApiTables(
		state.access_token ?? '',
		state.base?.id ?? ''
	);

	const selectedTable = tables?.find( table => table.id === currentTableId ) ?? null;
	const availableTableFields: string[] =
		selectedTable?.fields
			.filter( field => SUPPORTED_AIRTABLE_TYPES.includes( field.type ) )
			.map( field => field.name ) ?? [];

	const baseOptions = [
		{
			...defaultSelectBaseOption,
			label: __( 'Select a base', 'remote-data-blocks' ),
		},
		...( bases ?? [] ).map( ( { name, id } ) => ( { label: name, value: id } ) ),
	];
	const tableOptions = [
		{
			...defaultSelectTableOption,
			label: __( 'Select a table', 'remote-data-blocks' ),
		},
		...( tables ?? [] ).map( ( { name, id } ) => ( { label: name, value: id, disabled: false } ) ),
	];

	const onSaveClick = async () => {
		if ( ! validState || ! selectedTable ) {
			return;
		}

		const airtableConfig: AirtableConfig = {
			service: 'airtable',
			service_config: validState,
			uuid: uuid ?? null,
		};

		return onSave( airtableConfig, mode );
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
			if ( id === 'base' ) {
				const selectedBase = bases?.find( base => base.id === value );
				handleOnChange( 'base', { id: value, name: selectedBase?.name ?? '' } );
				return;
			}

			if ( id === 'tables' ) {
				if ( selectedTable ) {
					return;
				}

				handleOnChange( 'tables', [] );
				return;
			}

			handleOnChange( id, value );
		}
	};

	let connectionMessage: React.ReactNode = (
		<span>
			<a href="https://support.airtable.com/docs/creating-personal-access-tokens" target="_label">
				{ __( 'How do I get my token?', 'remote-data-blocks' ) }
			</a>
		</span>
	);

	if ( fetchingUserId ) {
		connectionMessage = __( 'Validating connection...', 'remote-data-blocks' );
	} else if ( userIdError ) {
		connectionMessage = getConnectionMessage(
			'error',
			__( 'Connection failed. Please verify your access token.', 'remote-data-blocks' )
		);
	} else if ( userId ) {
		connectionMessage = getConnectionMessage(
			'success',
			__( 'Connection successful.', 'remote-data-blocks' )
		);
	}

	const shouldAllowContinue = userId !== null;
	const shouldAllowSubmit =
		bases !== null && tables !== null && Boolean( state.base ) && Boolean( selectedTable );

	let basesHelpText: React.ReactNode = 'Select a base from which to fetch data.';
	if ( userId ) {
		if ( basesError ) {
			basesHelpText = __(
				'Failed to fetch bases. Please check that your access token has the `schema.bases:read` Scope.'
			);
		} else if ( fetchingBases ) {
			basesHelpText = __( 'Fetching bases...' );
		} else if ( bases?.length === 0 ) {
			basesHelpText = __( 'No bases found.' );
		}
	}

	let tablesHelpText: string = __(
		'Select a table to attach with this data source.',
		'remote-data-blocks'
	);
	if ( bases?.length && state.base ) {
		if ( tablesError ) {
			tablesHelpText = __(
				'Failed to fetch tables. Please check that your access token has the `schema.tables:read` Scope.',
				'remote-data-blocks'
			);
		} else if ( fetchingTables ) {
			tablesHelpText = __( 'Fetching tables...', 'remote-data-blocks' );
		} else if ( tables ) {
			if ( selectedTable ) {
				tablesHelpText = sprintf(
					__( '%s fields found', 'remote-data-blocks' ),
					selectedTable.fields.length
				);
			}

			if ( ! tables.length ) {
				tablesHelpText = __( 'No tables found', 'remote-data-blocks' );
			}
		}

		tablesHelpText = __( 'Select a table from which to fetch data.', 'remote-data-blocks' );
	}

	return (
		<>
			<DataSourceForm onSave={ onSaveClick }>
				<DataSourceForm.Setup
					displayName={ state.display_name ?? '' }
					handleOnChange={ handleOnChange }
					heading={ { icon: AirtableIconWithText, width: '113.81px', height: '25px' } }
					inputIcon={ AirtableIcon }
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
						value={ selectedTable?.id ?? '' }
						onChange={ setCurrentTableId }
						options={ tableOptions }
						help={ tablesHelpText }
						disabled={ fetchingTables || ! tables?.length }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>

					{ selectedTable && availableTableFields.length ? (
						<CustomFormFieldToken
							label={ __( 'Fields', 'remote-data-blocks' ) }
							onChange={ selection => {
								let newTableFields: string[];
								if ( selection.includes( 'Select All' ) ) {
									newTableFields = Array.from( new Set( availableTableFields ) );
								} else if ( selection.includes( 'Deselect All' ) ) {
									newTableFields = [];
								} else {
									newTableFields = Array.from(
										new Set(
											selection
												.filter( item => item !== 'Select All' && item !== 'Deselect All' )
												.map( item => ( 'object' === typeof item ? item.value : item ) )
										)
									);
								}
								setTableFields( newTableFields );
								handleOnChange( 'tables', [
									{
										id: selectedTable.id,
										name: selectedTable.name,
										output_query_mappings: newTableFields
											.map( key => {
												const field = selectedTable.fields.find(
													tableField => tableField.name === key
												);
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
								] );
							} }
							suggestions={ [
								...( tableFields.length === availableTableFields.length
									? [ 'Deselect All' ]
									: [ 'Select All' ] ),
								...availableTableFields,
							] }
							value={ tableFields }
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
						selectedTable && <Spinner />
					) }
				</DataSourceForm.Scope>
			</DataSourceForm>
		</>
	);
};
