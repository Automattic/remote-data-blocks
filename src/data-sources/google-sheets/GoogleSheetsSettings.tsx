import { TextareaControl, SelectControl } from '@wordpress/components';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { DataSourceForm } from '@/data-sources/components/DataSourceForm';
import { FieldsSelection } from '@/data-sources/components/FieldsSelection';
import { GOOGLE_SHEETS_API_SCOPES } from '@/data-sources/constants';
import { useDataSources } from '@/data-sources/hooks/useDataSources';
import {
	useGoogleSpreadsheetsOptions,
	useGoogleSheetsOptions,
	useGoogleSheetFields,
} from '@/data-sources/hooks/useGoogleApi';
import { useGoogleAuth } from '@/data-sources/hooks/useGoogleAuth';
import {
	GoogleSheetsConfig,
	GoogleSheetsServiceConfig,
	SettingsComponentProps,
} from '@/data-sources/types';
import { getConnectionMessage } from '@/data-sources/utils';
import { useForm, ValidationRules } from '@/hooks/useForm';
import { GoogleSheetsIcon, GoogleSheetsIconWithText } from '@/settings/icons/GoogleSheetsIcon';
import { GoogleServiceAccountKey } from '@/types/google';
import { SelectOption } from '@/types/input';
import { isPositiveIntegerString, safeParseJSON } from '@/utils/string';

const SERVICE_CONFIG_VERSION = 1;

const defaultSelectOption: SelectOption = {
	disabled: true,
	label: __( 'Select an option', 'remote-data-blocks' ),
	value: '',
};

const validationRules: ValidationRules< GoogleSheetsServiceConfig > = {
	credentials: ( state: Partial< GoogleSheetsServiceConfig > ) => {
		if ( ! state.credentials ) {
			return __(
				'Please provide credentials JSON for the service account to connect to Google Sheets.',
				'remote-data-blocks'
			);
		}

		return null;
	},
};

export const GoogleSheetsSettings = ( {
	mode,
	uuid,
	config,
}: SettingsComponentProps< GoogleSheetsConfig > ) => {
	const { onSave } = useDataSources< GoogleSheetsConfig >( false );

	const { state, errors, handleOnChange, validState } = useForm< GoogleSheetsServiceConfig >( {
		initialValues: config?.service_config ?? { __version: SERVICE_CONFIG_VERSION },
		validationRules,
	} );

	const currentSheet = state?.sheets?.length ? state.sheets[ 0 ] : null;
	const currentSheetIdString = currentSheet ? currentSheet.id.toString() : '';
	const currentSheetTitle = currentSheet ? currentSheet.name : '';

	const [ spreadsheetOptions, setSpreadsheetOptions ] = useState< SelectOption[] >( [
		{
			...defaultSelectOption,
			label: __( 'Auto-filled on successful connection.', 'remote-data-blocks' ),
		},
	] );
	const [ sheetOptions, setSheetOptions ] = useState< SelectOption[] >( [
		{
			...defaultSelectOption,
			label: __( 'Auto-filled on valid spreadsheet.', 'remote-data-blocks' ),
		},
	] );

	const { fetchingToken, token, tokenError } = useGoogleAuth(
		JSON.stringify( state.credentials ),
		GOOGLE_SHEETS_API_SCOPES
	);
	const { spreadsheets, isLoadingSpreadsheets, errorSpreadsheets } =
		useGoogleSpreadsheetsOptions( token );
	const { sheets, isLoadingSheets, errorSheets } = useGoogleSheetsOptions(
		token,
		state.spreadsheet?.id ?? ''
	);
	const { sheetFields, isLoadingSheetFields, errorSheetFields } = useGoogleSheetFields(
		token,
		state.spreadsheet?.id ?? '',
		currentSheetTitle
	);
	const availableSheetFields = sheetFields ?? [];
	const showFieldsSelection =
		currentSheet &&
		isLoadingSheetFields === false &&
		availableSheetFields.length > 0 &&
		! errorSheetFields;
	const selectedSheetFields = state.sheets?.[ 0 ]?.output_query_mappings?.length
		? state.sheets[ 0 ].output_query_mappings.map( mapping => mapping.key )
		: [];

	const onSaveClick = async () => {
		if ( ! validState ) {
			return;
		}

		const data: GoogleSheetsConfig = {
			service: 'google-sheets',
			service_config: validState,
			uuid: uuid ?? null,
		};

		return onSave( data, mode );
	};

	const onCredentialsChange = ( nextValue: string ) => {
		const credentials = safeParseJSON< GoogleServiceAccountKey >( nextValue );
		if ( credentials ) {
			handleOnChange( 'credentials', credentials );
		}
	};

	const onSheetChange = ( value: string ) => {
		if ( isPositiveIntegerString( value ) ) {
			const selectedSheet = sheets?.find( sheet => sheet.value === value );
			handleOnChange( 'sheets', [
				{
					id: value,
					name: selectedSheet?.label ?? '',
					output_query_mappings: [],
				},
			] );
		}
	};

	const onSpreadsheetChange = ( value: string ) => {
		const selectedSpreadsheet = spreadsheets?.find( spreadsheet => spreadsheet.value === value );
		handleOnChange( 'spreadsheet', { id: value, name: selectedSpreadsheet?.label ?? '' } );
		handleOnChange( 'sheets', [] );
	};

	const onSheetsFieldsChange = ( newFields: string[] ) => {
		if ( ! currentSheet ) {
			return;
		}

		handleOnChange( 'sheets', [
			{
				...currentSheet,
				output_query_mappings: newFields.map( key => ( {
					key,
					name: key,
					path: `$.${ key }`,
					type: 'string',
				} ) ),
			},
		] );
	};

	const credentialsHelpText = useMemo( () => {
		if ( fetchingToken ) {
			return __( 'Checking credentials...', 'remote-data-blocks' );
		} else if ( errors.credentials ) {
			return errors.credentials;
		} else if ( tokenError ) {
			const errorMessage = tokenError.message ?? __( 'Unknown error', 'remote-data-blocks' );
			return getConnectionMessage(
				'error',
				__( 'Failed to generate token using provided credentials: ', 'remote-data-blocks' ) +
					' ' +
					errorMessage
			);
		} else if ( token ) {
			return getConnectionMessage(
				'success',
				__( 'Credentials are valid. Token generated successfully.', 'remote-data-blocks' )
			);
		}
		return __(
			'Please provide credentials JSON to connect to Google Sheets.',
			'remote-data-blocks'
		);
	}, [ fetchingToken, token, tokenError, errors.credentials ] );

	const shouldAllowSubmit = state.spreadsheet && state.sheets?.length;

	const spreadsheetHelpText = useMemo( () => {
		if ( token ) {
			if ( errorSpreadsheets ) {
				const errorMessage =
					errorSpreadsheets?.message ?? __( 'Unknown error', 'remote-data-blocks' );
				return __( 'Failed to fetch spreadsheets.', 'remote-data-blocks' ) + ' ' + errorMessage;
			} else if ( isLoadingSpreadsheets ) {
				return __( 'Fetching spreadsheets...', 'remote-data-blocks' );
			} else if ( spreadsheets?.length === 0 ) {
				return __( 'No spreadsheets found', 'remote-data-blocks' );
			}
		}

		return __( 'Select a spreadsheet from which to fetch data.', 'remote-data-blocks' );
	}, [ token, errorSpreadsheets, isLoadingSpreadsheets, spreadsheets ] );

	const sheetHelpText = useMemo( () => {
		if ( token ) {
			if ( errorSheets ) {
				const errorMessage = errorSheets?.message ?? __( 'Unknown error', 'remote-data-blocks' );
				return __( 'Failed to fetch sheets.', 'remote-data-blocks' ) + ' ' + errorMessage;
			} else if ( isLoadingSheets ) {
				return __( 'Fetching sheets...', 'remote-data-blocks' );
			} else if ( sheets?.length === 0 ) {
				return __( 'No sheets found', 'remote-data-blocks' );
			}
		}

		return __( 'Select a sheet from which to fetch data.', 'remote-data-blocks' );
	}, [ token, errorSheets, isLoadingSheets, sheets ] );

	useEffect( () => {
		if ( ! spreadsheets?.length ) {
			return;
		}

		setSpreadsheetOptions( [
			{
				...defaultSelectOption,
				label: __( 'Select a spreadsheet', 'remote-data-blocks' ),
			},
			...( spreadsheets ?? [] ).map( ( { label, value } ) => ( { label, value } ) ),
		] );
	}, [ spreadsheets ] );

	useEffect( () => {
		if ( ! state.spreadsheet ) {
			return;
		}

		setSheetOptions( [
			{
				...defaultSelectOption,
				label: __( 'Select a sheet', 'remote-data-blocks' ),
			},
			...( sheets ?? [] ).map( ( { label, value } ) => ( { label, value } ) ),
		] );
	}, [ state.spreadsheet, sheets ] );

	const getCustomHelpText = () => {
		if ( ! sheets?.length ) {
			return __( 'Please select a sheet first.', 'remote-data-blocks' );
		}

		if ( isLoadingSheetFields ) {
			return __( 'Fetching fields...', 'remote-data-blocks' );
		}

		return null;
	};

	return (
		<DataSourceForm onSave={ onSaveClick }>
			<DataSourceForm.Setup
				canProceed={ Boolean( token ) }
				displayName={ state.display_name ?? '' }
				handleOnChange={ handleOnChange }
				heading={ {
					icon: GoogleSheetsIconWithText,
					width: '191px',
					height: '32px',
					verticalAlign: 'text-top',
				} }
				inputIcon={ GoogleSheetsIcon }
			>
				<TextareaControl
					label={ __( 'Credentials', 'remote-data-blocks' ) }
					value={ state.credentials ? JSON.stringify( state.credentials, null, 2 ) : '' }
					onChange={ onCredentialsChange }
					help={ credentialsHelpText }
					rows={ 10 }
					className="code-input"
					__nextHasNoMarginBottom
				/>
			</DataSourceForm.Setup>
			<DataSourceForm.Scope canProceed={ Boolean( shouldAllowSubmit ) }>
				<SelectControl
					id="spreadsheet"
					label={ __( 'Spreadsheet', 'remote-data-blocks' ) }
					value={ state.spreadsheet?.id ?? '' }
					onChange={ onSpreadsheetChange }
					options={ spreadsheetOptions }
					help={ spreadsheetHelpText }
					disabled={ fetchingToken || ! spreadsheets?.length }
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>

				<SelectControl
					id="sheets"
					label={ __( 'Sheet', 'remote-data-blocks' ) }
					value={ currentSheetIdString }
					onChange={ onSheetChange }
					options={ sheetOptions }
					help={ sheetHelpText }
					disabled={ fetchingToken || ! sheets?.length }
					__next40pxDefaultSize
				/>
				<FieldsSelection
					selectedFields={ selectedSheetFields }
					availableFields={ availableSheetFields }
					onFieldsChange={ onSheetsFieldsChange }
					disabled={ ! showFieldsSelection }
					customHelpText={ getCustomHelpText() }
				/>
			</DataSourceForm.Scope>
		</DataSourceForm>
	);
};
