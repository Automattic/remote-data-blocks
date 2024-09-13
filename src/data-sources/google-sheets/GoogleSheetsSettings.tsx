import {
	Button,
	ButtonGroup,
	__experimentalHeading as Heading,
	Panel,
	PanelBody,
	PanelRow,
	SelectControl,
	TextareaControl,
	TextControl,
} from '@wordpress/components';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { ChangeEvent } from 'react';

import { GOOGLE_SHEETS_API_SCOPES } from '@/data-sources/constants';
import { GoogleSheetsFormState } from '@/data-sources/google-sheets/types';
import { useDataSources } from '@/data-sources/hooks/useDataSources';
import {
	useGoogleSheetsOptions,
	useGoogleSpreadsheetsOptions,
} from '@/data-sources/hooks/useGoogleApi';
import { useGoogleAuth } from '@/data-sources/hooks/useGoogleAuth';
import { GoogleSheetsConfig } from '@/data-sources/types';
import { useForm, ValidationRules } from '@/hooks/useForm';
import { useSettingsContext } from '@/settings/hooks/useSettingsNav';
import { StringIdName } from '@/types/common';
import { GoogleServiceAccountKey } from '@/types/google';
import { SelectOption } from '@/types/input';

export interface GoogleSheetsSettingsProps {
	mode: 'add' | 'edit';
	uuid?: string;
	config?: GoogleSheetsConfig;
}

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
	label: '',
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
}: GoogleSheetsSettingsProps ) => {
	const { goToMainScreen } = useSettingsContext();
	const { updateDataSource, addDataSource } = useDataSources( false );

	const { state, errors, handleOnChange } = useForm< GoogleSheetsFormState >( {
		initialValues: getInitialStateFromConfig( config ),
		validationRules,
	} );

	const [ spreadsheetOptions, setSpreadsheetOptions ] = useState< SelectOption[] >( [
		defaultSelectOption,
	] );
	const [ sheetOptions, setSheetOptions ] = useState< SelectOption[] >( [ defaultSelectOption ] );

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

	const handleSaveError = ( error: unknown ) => {
		console.error( error );
	};

	const onSaveClick = () => {
		if ( ! state.spreadsheet || ! state.sheet || ! state.credentials ) {
			// TODO: Error handling
			return;
		}

		const data: GoogleSheetsConfig = {
			uuid: uuidFromProps ?? '',
			service: 'google-sheets',
			display_name: state.display_name,
			spreadsheet: state.spreadsheet,
			sheet: {
				id: parseInt( state.sheet.id, 10 ),
				name: state.sheet.name,
			},
			credentials: JSON.parse( state.credentials ) as GoogleServiceAccountKey,
		};

		if ( mode === 'add' ) {
			void addDataSource( data ).then( goToMainScreen ).catch( handleSaveError );
		}
		void updateDataSource( data ).then( goToMainScreen ).catch( handleSaveError );
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

	const onDisplayNameChange = ( displayName: string ) => {
		handleOnChange( 'display_name', displayName );
	};

	const credentialsHelpText = useMemo( () => {
		if ( fetchingToken ) {
			return __( 'Checking credentials...', 'remote-data-blocks' );
		} else if ( errors.credentials ) {
			return errors.credentials;
		} else if ( tokenError ) {
			const errorMessage = tokenError.message ?? __( 'Unknown error', 'remote-data-blocks' );
			return (
				__( 'Failed to generate token using provided credentials: ', 'remote-data-blocks' ) +
				' ' +
				errorMessage
			);
		} else if ( token ) {
			return sprintf(
				__( 'Credentials are valid. Token generated successfully.', 'remote-data-blocks' ),
				token
			);
		}
		return __(
			'Please provide credentials JSON to connect to Google Sheets.',
			'remote-data-blocks'
		);
	}, [ fetchingToken, token, tokenError, errors.credentials ] );

	const shouldAllowSubmit = useMemo( () => {
		return ! state.spreadsheet || ! state.sheet || ! state.credentials;
	}, [ state.spreadsheet, state.sheet, state.credentials ] );

	const spreadsheetHelpText = useMemo( () => {
		if ( token ) {
			if ( errorSpreadsheets ) {
				const errorMessage =
					errorSpreadsheets?.message ?? __( 'Unknown error', 'remote-data-blocks' );
				return __( 'Failed to fetch spreadsheets.', 'remote-data-blocks' ) + ' ' + errorMessage;
			} else if ( isLoadingSpreadsheets ) {
				return __( 'Fetching spreadsheets...', 'remote-data-blocks' );
			} else if ( spreadsheets ) {
				if ( state.spreadsheet ) {
					const selectedSpreadsheet = spreadsheets.find(
						spreadsheet => spreadsheet.value === state.spreadsheet?.id
					);
					return sprintf(
						__( 'Selected spreadsheet: %s | id: %s', 'remote-data-blocks' ),
						selectedSpreadsheet?.label ?? '',
						selectedSpreadsheet?.value ?? ''
					);
				}
				if ( spreadsheets.length ) {
					return '';
				}
				return __( 'No spreadsheets found', 'remote-data-blocks' );
			}
			return '';
		}
	}, [ token, errorSpreadsheets, isLoadingSpreadsheets, state.spreadsheet, spreadsheets ] );

	const sheetHelpText = useMemo( () => {
		if ( token ) {
			if ( errorSheets ) {
				const errorMessage = errorSheets?.message ?? __( 'Unknown error', 'remote-data-blocks' );
				return __( 'Failed to fetch sheets.', 'remote-data-blocks' ) + ' ' + errorMessage;
			} else if ( isLoadingSheets ) {
				return __( 'Fetching sheets...', 'remote-data-blocks' );
			} else if ( sheets ) {
				if ( state.sheet ) {
					const selectedSheet = sheets.find( sheet => sheet.value === state.sheet?.id );
					return sprintf(
						__( 'Selected sheet: %s | id: %s', 'remote-data-blocks' ),
						selectedSheet?.label ?? '',
						selectedSheet?.value ?? ''
					);
				}
				if ( sheets.length ) {
					return '';
				}
				return __( 'No sheets found', 'remote-data-blocks' );
			}
			return '';
		}
	}, [ token, errorSheets, isLoadingSheets, state.sheet, sheets ] );

	useEffect( () => {
		setSpreadsheetOptions( [
			defaultSelectOption,
			...( spreadsheets ?? [] ).map( ( { label, value } ) => ( { label, value } ) ),
		] );
	}, [ spreadsheets ] );

	useEffect( () => {
		setSheetOptions( [
			defaultSelectOption,
			...( sheets ?? [] ).map( ( { label, value } ) => ( { label, value } ) ),
		] );
	}, [ sheets ] );

	return (
		<Panel>
			<PanelBody>
				<Heading>
					{ mode === 'add'
						? __( 'Add a new Google Sheets Data Source' )
						: __( 'Edit Google Sheets Data Source' ) }
				</Heading>
				<PanelRow>
					<TextControl
						label={ __( 'Display Name', 'remote-data-blocks' ) }
						value={ state.display_name }
						onChange={ onDisplayNameChange }
					/>
				</PanelRow>
				<PanelRow>
					<TextareaControl
						label={ __( 'Credentials', 'remote-data-blocks' ) }
						value={ state.credentials }
						onChange={ onCredentialsChange }
						help={ credentialsHelpText }
						rows={ 14 }
					/>
				</PanelRow>
				<PanelRow>
					<SelectControl
						id="spreadsheet"
						label={ __( 'Select Spreadsheet', 'remote-data-blocks' ) }
						value={ state.spreadsheet?.id ?? '' }
						onChange={ onSelectChange }
						options={ spreadsheetOptions }
						help={ spreadsheetHelpText }
						disabled={ fetchingToken || ! spreadsheets?.length }
					/>
				</PanelRow>
				<PanelRow>
					<SelectControl
						id="sheet"
						label={ __( 'Select Sheet', 'remote-data-blocks' ) }
						value={ state.sheet?.id ?? '' }
						onChange={ onSelectChange }
						options={ sheetOptions }
						help={ sheetHelpText }
						disabled={ fetchingToken || ! sheets?.length }
					/>
				</PanelRow>
			</PanelBody>
			<ButtonGroup className="settings-form-cta-button-group">
				<Button variant="primary" onClick={ onSaveClick } disabled={ shouldAllowSubmit }>
					{ __( 'Save', 'remote-data-blocks' ) }
				</Button>
				<Button variant="secondary" onClick={ goToMainScreen }>
					{ __( 'Cancel', 'remote-data-blocks' ) }
				</Button>
			</ButtonGroup>
		</Panel>
	);
};
