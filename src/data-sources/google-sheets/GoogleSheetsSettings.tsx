import {
	Card,
	CardHeader,
	CardBody,
	TextareaControl,
	SelectControl,
	ButtonGroup,
	Button,
} from '@wordpress/components';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { ChangeEvent } from 'react';

import { getConnectionMessage } from '../utils';
import { SlugInput } from '@/data-sources/components/SlugInput';
import { GOOGLE_SHEETS_API_SCOPES } from '@/data-sources/constants';
import { GoogleSheetsFormState } from '@/data-sources/google-sheets/types';
import { useDataSources } from '@/data-sources/hooks/useDataSources';
import {
	useGoogleSpreadsheetsOptions,
	useGoogleSheetsOptions,
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
	slug: '',
	spreadsheet: null,
	sheet: null,
	credentials: '',
};

const getInitialStateFromConfig = ( config?: GoogleSheetsConfig ): GoogleSheetsFormState => {
	if ( ! config ) {
		return initialState;
	}

	return {
		slug: config.slug,
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
}: GoogleSheetsSettingsProps ) => {
	const { goToMainScreen } = useSettingsContext();
	const { updateDataSource, addDataSource, slugConflicts, loadingSlugConflicts } =
		useDataSources( false );

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
			slug: state.slug,
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

	/**
	 * Handle the slug change. Only accepts valid slugs which only contain alphanumeric characters and dashes.
	 * @param slug The slug to set.
	 */
	const onSlugChange = ( slug: string | undefined ) => {
		handleOnChange( 'slug', slug ?? '' );
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
		return (
			! state.spreadsheet ||
			! state.sheet ||
			! state.credentials ||
			loadingSlugConflicts ||
			slugConflicts
		);
	}, [ state.spreadsheet, state.sheet, state.credentials, loadingSlugConflicts, slugConflicts ] );

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
		<Card className="add-update-data-source-card">
			<CardHeader>
				<h2>
					{ mode === 'add'
						? __( 'Add Google Sheets Data Source' )
						: __( 'Edit Google Sheets Data Source' ) }
				</h2>
			</CardHeader>
			<CardBody>
				<form>
					<div className="form-group">
						<SlugInput slug={ state.slug } onChange={ onSlugChange } uuid={ uuidFromProps } />
					</div>

					<div className="form-group">
						<TextareaControl
							label={ __( 'Credentials', 'remote-data-blocks' ) }
							value={ state.credentials }
							onChange={ onCredentialsChange }
							help={ credentialsHelpText }
							rows={ 14 }
						/>
					</div>

					<div className="form-group">
						<SelectControl
							id="spreadsheet"
							label={ __( 'Spreadsheet', 'remote-data-blocks' ) }
							value={ state.spreadsheet?.id ?? '' }
							onChange={ onSelectChange }
							options={ spreadsheetOptions }
							help={ spreadsheetHelpText }
							disabled={ fetchingToken || ! spreadsheets?.length }
							__next40pxDefaultSize
						/>
					</div>

					<div className="form-group">
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
					</div>

					<div className="form-group">
						<ButtonGroup className="form-actions">
							<Button variant="primary" onClick={ onSaveClick } disabled={ shouldAllowSubmit }>
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
