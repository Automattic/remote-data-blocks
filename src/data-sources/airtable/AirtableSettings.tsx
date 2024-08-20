import {
	Button,
	ButtonGroup,
	__experimentalHeading as Heading,
	SelectControl,
	Panel,
	PanelBody,
	PanelRow,
} from '@wordpress/components';
import { InputChangeCallback } from '@wordpress/components/build-types/input-control/types';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { ChangeEvent } from 'react';

import {
	useAirtableApiBases,
	useAirtableApiTables,
	useAirtableApiUserId,
} from './airtable-api-hooks';
import { AirtableFormState } from './types';
import { useForm } from '../../hooks/useForm';
import PasswordInputControl from '../../settings/PasswordInputControl';
import { useSettingsContext } from '../../settings/hooks/useSettingsNav';
import { SelectOption } from '../../types/input';
import { useDataSources } from '../hooks/useDataSources';
import { AirtableConfig } from '../types';

export interface AirtableSettingsProps {
	mode: 'add' | 'edit';
	uuid?: string;
	config?: AirtableConfig;
}

const initialState: AirtableFormState = {
	token: '',
	base: '',
	table: '',
};

const getInitialStateFromConfig = ( config?: AirtableConfig ): AirtableFormState => {
	if ( ! config ) {
		return initialState;
	}
	return {
		token: config.token,
		base: config.base,
		table: config.table,
	};
};

const defaultSelectBaseOption: SelectOption = {
	label: '',
	value: '',
};

const defaultSelectTableOption: SelectOption = {
	label: '',
	value: '',
};

export const AirtableSettings = ( {
	mode,
	uuid: uuidFromProps,
	config,
}: AirtableSettingsProps ) => {
	const { goToMainScreen } = useSettingsContext();
	const { updateDataSource, addDataSource } = useDataSources( false );

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
		bases ? state.base : ''
	);

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
		};

		if ( mode === 'add' ) {
			void addDataSource( airtableConfig ).then( goToMainScreen );
		}
		void updateDataSource( airtableConfig ).then( goToMainScreen );
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
			handleOnChange( id, value );
		}
	};

	const connectionMessage = useMemo( () => {
		if ( fetchingUserId ) {
			return __( 'Checking connection...', 'remote-data-blocks' );
		} else if ( userIdError ) {
			return __( 'Connection failed. Please check your API key.', 'remote-data-blocks' );
		} else if ( userId ) {
			return sprintf( __( 'Connection successful. User ID: %s', 'remote-data-blocks' ), userId );
		}
		return '';
	}, [ fetchingUserId, userId, userIdError ] );

	const basesHelpText = useMemo( () => {
		if ( userId ) {
			if ( basesError ) {
				return __(
					'Failed to fetch bases. Please check that your access token has the `schema.bases:read` Scope.'
				);
			} else if ( fetchingBases ) {
				return __( 'Fetching Bases...' );
			} else if ( bases ) {
				if ( state.base ) {
					const selectedBase = bases.find( ( { id } ) => id === state.base );
					return selectedBase
						? sprintf(
								__( 'Selected base: %s | id: %s', 'remote-data-blocks' ),
								selectedBase.name,
								selectedBase.id
						  )
						: sprintf( __( 'Invalid base selected: %s', 'remote-data-blocks' ), state.base );
				}
				if ( bases.length ) {
					return '';
				}
				return __( 'No Bases found' );
			}
			return '';
		}
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
					const selectedTable = tables.find( ( { id } ) => id === state.table );
					return selectedTable
						? sprintf(
								__( 'Selected table: %s | Fields: %s', 'remote-data-blocks' ),
								selectedTable.name,
								selectedTable.fields.map( field => field.name ).join( ', ' )
						  )
						: sprintf( __( 'Invalid table selected: %s', 'remote-data-blocks' ), state.table );
				}
				if ( tables.length ) {
					return __( 'Select a table from which to fetch data.', 'remote-data-blocks' );
				}
				return __( 'No tables found', 'remote-data-blocks' );
			}

			return '';
		}
	}, [ bases, fetchingTables, state.base, state.table, tables, tablesError ] );

	useEffect( () => {
		setBaseOptions( [
			defaultSelectBaseOption,
			...( bases ?? [] ).map( ( { name, id } ) => ( { label: name, value: id } ) ),
		] );
	}, [ bases ] );

	useEffect( () => {
		if ( tables ) {
			setTableOptions( [
				defaultSelectTableOption,
				...tables.map( ( { name, id } ) => ( { label: name, value: id, disabled: false } ) ),
			] );
		}
	}, [ tables ] );

	return (
		<Panel>
			<PanelBody>
				<Heading>
					{ mode === 'add'
						? __( 'Add a new Airtable Data Source' )
						: __( 'Edit Airtable Data Source' ) }
				</Heading>
				<PanelRow>
					<PasswordInputControl
						label={ __( 'Airtable Access Token', 'remote-data-blocks' ) }
						onChange={ onTokenInputChange }
						value={ state.token }
					/>
				</PanelRow>
				<PanelRow>{ connectionMessage }</PanelRow>
				<PanelRow>
					<SelectControl
						id="base"
						label={ __( 'Select Base', 'remote-data-blocks' ) }
						value={ state.base }
						onChange={ onSelectChange }
						options={ baseOptions }
						help={ basesHelpText }
						disabled={ fetchingBases || ! bases?.length }
					/>
				</PanelRow>
				<PanelRow>
					<SelectControl
						id="table"
						label={ __( 'Select Table', 'remote-data-blocks' ) }
						value={ state.table }
						onChange={ onSelectChange }
						options={ tableOptions }
						help={ tablesHelpText }
						disabled={ fetchingTables || ! tables?.length }
					/>
				</PanelRow>
			</PanelBody>
			<ButtonGroup>
				<Button
					variant="primary"
					onClick={ onSaveClick }
					disabled={ bases === null || tables === null || ! state.base || ! state.table }
				>
					{ __( 'Save', 'remote-data-blocks' ) }
				</Button>
			</ButtonGroup>
		</Panel>
	);
};
