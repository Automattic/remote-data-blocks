import { TextareaControl, SelectControl } from '@wordpress/components';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { ChangeEvent } from 'react';

import { DataSourceForm } from '../components/DataSourceForm';
import { getConnectionMessage } from '../utils';
import { GOOGLE_SHEETS_API_SCOPES } from '@/data-sources/constants';
import { GoogleSheetsFormState } from '@/data-sources/google-sheets/types';
import { useDataSources } from '@/data-sources/hooks/useDataSources';
import {
	useGoogleSpreadsheetsOptions,
	useGoogleSheetsOptions,
} from '@/data-sources/hooks/useGoogleApi';
import { useGoogleAuth } from '@/data-sources/hooks/useGoogleAuth';
import { GoogleSheetsConfig, SettingsComponentProps } from '@/data-sources/types';
import { useForm, ValidationRules } from '@/hooks/useForm';
import { useSettingsContext } from '@/settings/hooks/useSettingsNav';
import { GoogleSheetsIcon, GoogleSheetsIconWithText } from '@/settings/icons/GoogleSheetsIcon';
import { StringIdName } from '@/types/common';
import { GoogleServiceAccountKey } from '@/types/google';
import { SelectOption } from '@/types/input';

const initialState: GoogleSheetsFormState = {
	display_name: '',
	spreadsheet: null,
	sheet: null,
	credentials: '',
};

const getInitialStateFromConfig = ( config?: GoogleSheetsConfig ): GoogleSheetsFormState => {
	if ( ! config ) {
		return initialState;
	}

	return {
		display_name: config.display_name,
		spreadsheet: config.spreadsheet,
		sheet: config.sheet
			? {
					id: config.sheet.id.toString(),
					name: config.sheet.name,
			  }
			: null,
		credentials: JSON.stringify( config.credentials ),
	};
};

const defaultSelectOption: SelectOption = {
	disabled: true,
	label: __( 'Select an option', 'remote-data-blocks' ),
	value: '',
};

const validationRules: ValidationRules< GoogleSheetsFormState > = {
	credentials: ( state: GoogleSheetsFormState ) => {
		if ( ! state.credentials ) {
			return __(
				'Please provide credentials JSON for the service account to connect to Google Sheets.',
				'remote-data-blocks'
			);
		}

		try {
			JSON.parse( state.credentials );
		} catch ( error ) {
			return __( 'Credentials are not valid JSON', 'remote-data-blocks' );
		}
		return null;
	},
};

export const GoogleSheetsSettings = ( {
	mode,
	uuid: uuidFromProps,
	config,
}: SettingsComponentProps< GoogleSheetsConfig > ) => {
	const { goToMainScreen } = useSettingsContext();
	const { updateDataSource, addDataSource } = useDataSources( false );

	const { state, errors, handleOnChange } = useForm< GoogleSheetsFormState >( {
		initialValues: getInitialStateFromConfig( config ),
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
		state.credentials,
		GOOGLE_SHEETS_API_SCOPES
	);
	const { spreadsheets, isLoadingSpreadsheets, errorSpreadsheets } =
		useGoogleSpreadsheetsOptions( token );
	const { sheets, isLoadingSheets, errorSheets } = useGoogleSheetsOptions(
		token,
		state.spreadsheet?.id ?? ''
	);

	const [ newUUID, setNewUUID ] = useState< string | null >( uuidFromProps ?? null );

	const onSaveClick = async () => {
		if ( ! state.spreadsheet || ! state.sheet || ! state.credentials ) {
			// TODO: Error handling
			return;
		}

		const data: GoogleSheetsConfig = {
			display_name: state.display_name,
			uuid: uuidFromProps ?? '',
			newUUID: newUUID ?? '',
			service: 'google-sheets',

			spreadsheet: state.spreadsheet,
			sheet: {
				id: parseInt( state.sheet.id, 10 ),
				name: state.sheet.name,
			},
			credentials: JSON.parse( state.credentials ) as GoogleServiceAccountKey,
		};

		if ( mode === 'add' ) {
			await addDataSource( data );
		} else {
			await updateDataSource( data );
		}
		goToMainScreen();
	};

	const onCredentialsChange = ( nextValue: string ) => {
		handleOnChange( 'credentials', nextValue );
	};

	const onSelectChange = (
		value: string,
		extra?: { event?: ChangeEvent< HTMLSelectElement > }
	) => {
		if ( extra?.event ) {
			const { id } = extra.event.target;
			let newValue: StringIdName | null = null;
			if ( id === 'spreadsheet' ) {
				const selectedSpreadsheet = spreadsheets?.find(
					spreadsheet => spreadsheet.value === value
				);
				newValue = { id: value, name: selectedSpreadsheet?.label ?? '' };
			} else if ( id === 'sheet' ) {
				const selectedSheet = sheets?.find( sheet => sheet.value === value );
				newValue = { id: value, name: selectedSheet?.label ?? '' };
			}
			handleOnChange( id, newValue );
		}
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
	}, [ token, errorSpreadsheets, isLoadingSpreadsheets, state.spreadsheet, spreadsheets ] );

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
	}, [ token, errorSheets, isLoadingSheets, state.sheet, sheets ] );

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
				displayName={ state.display_name }
				handleOnChange={ handleOnChange }
				heading={ {
					icon: GoogleSheetsIconWithText,
					width: '191px',
					height: '32px',
					verticalAlign: 'text-top',
				} }
				inputIcon={ GoogleSheetsIcon }
				newUUID={ newUUID }
				setNewUUID={ setNewUUID }
				uuidFromProps={ uuidFromProps }
			>
				<TextareaControl
					label={ __( 'Credentials', 'remote-data-blocks' ) }
					value={ state.credentials }
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
					onChange={ onSelectChange }
					options={ spreadsheetOptions }
					help={ spreadsheetHelpText }
					disabled={ fetchingToken || ! spreadsheets?.length }
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>

				<SelectControl
					id="sheet"
					label={ __( 'Sheet', 'remote-data-blocks' ) }
					value={ state.sheet?.id ?? '' }
					onChange={ onSelectChange }
					options={ sheetOptions }
					help={ sheetHelpText }
					disabled={ fetchingToken || ! sheets?.length }
					__next40pxDefaultSize
				/>
			</DataSourceForm.Scope>
		</DataSourceForm>
	);
};
