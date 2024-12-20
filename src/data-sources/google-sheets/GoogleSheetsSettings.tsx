import { TextareaControl, SelectControl } from '@wordpress/components';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { DataSourceForm } from '../components/DataSourceForm';
import { getConnectionMessage } from '../utils';
import { GOOGLE_SHEETS_API_SCOPES } from '@/data-sources/constants';
import { useDataSources } from '@/data-sources/hooks/useDataSources';
import {
	useGoogleSpreadsheetsOptions,
	useGoogleSheetsOptions,
} from '@/data-sources/hooks/useGoogleApi';
import { useGoogleAuth } from '@/data-sources/hooks/useGoogleAuth';
import {
	GoogleSheetsConfig,
	GoogleSheetsServiceConfig,
	SettingsComponentProps,
} from '@/data-sources/types';
import { useForm, ValidationRules } from '@/hooks/useForm';
import { GoogleSheetsIcon, GoogleSheetsIconWithText } from '@/settings/icons/GoogleSheetsIcon';
import { SelectOption } from '@/types/input';
import { isPositiveIntegerString } from '@/utils/string';

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
		handleOnChange( 'credentials', JSON.parse( nextValue ) );
	};

	const onSheetChange = ( value: string ) => {
		if ( isPositiveIntegerString( value ) ) {
			const parsedValue = parseInt( value, 10 );
			const selectedSheet = sheets?.find( sheet => sheet.value === value );
			handleOnChange( 'sheet', { id: parsedValue, name: selectedSheet?.label ?? '' } );
		}
	};

	const onSpreadsheetChange = ( value: string ) => {
		const selectedSpreadsheet = spreadsheets?.find( spreadsheet => spreadsheet.value === value );
		handleOnChange( 'spreadsheet', { id: value, name: selectedSpreadsheet?.label ?? '' } );
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

	const shouldAllowSubmit = useMemo( () => {
		return state.spreadsheet && state.sheet && state.credentials;
	}, [ state.spreadsheet, state.sheet, state.credentials ] );

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
					id="sheet"
					label={ __( 'Sheet', 'remote-data-blocks' ) }
					value={ state.sheet?.id.toString() ?? '' }
					onChange={ onSheetChange }
					options={ sheetOptions }
					help={ sheetHelpText }
					disabled={ fetchingToken || ! sheets?.length }
					__next40pxDefaultSize
				/>
			</DataSourceForm.Scope>
		</DataSourceForm>
	);
};
