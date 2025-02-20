import { SelectControl, TextControl } from '@wordpress/components';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { DataSourceForm } from '@/data-sources/components/DataSourceForm';
import { ConfigSource } from '@/data-sources/constants';
import { useDataSources } from '@/data-sources/hooks/useDataSources';
import { useSalesforceD2CAuth } from '@/data-sources/hooks/useSalesforceD2CAuth';
import {
	SalesforceD2CConfig,
	SalesforceD2CServiceConfig,
	SettingsComponentProps,
} from '@/data-sources/types';
import { getConnectionMessage } from '@/data-sources/utils';
import { useForm, ValidationRules } from '@/hooks/useForm';
import SalesforceCommerceD2CIcon from '@/settings/icons/SalesforceCommerceD2CIcon';
import { SelectOption } from '@/types/input';

const SERVICE_CONFIG_VERSION = 1;

const defaultSelectOption: SelectOption = {
	disabled: true,
	label: __( 'Select an option', 'remote-data-blocks' ),
	value: '',
};

const validationRules: ValidationRules< SalesforceD2CServiceConfig > = {
	client_id: ( state: Partial< SalesforceD2CServiceConfig > ) => {
		if ( ! state.client_id ) {
			return __( 'Please provide a valid client ID.', 'remote-data-blocks' );
		}

		return null;
	},

	client_secret: ( state: Partial< SalesforceD2CServiceConfig > ) => {
		if ( ! state.client_secret ) {
			return __( 'Please provide a valid client secret.', 'remote-data-blocks' );
		}

		return null;
	},

	domain: ( state: Partial< SalesforceD2CServiceConfig > ) => {
		if ( ! state.domain ) {
			return __(
				'Please provide a valid domain. Example: For https://scomhello123usa456org.lightning.force.com, the domain will be scomhello123usa456org.',
				'remote-data-blocks'
			);
		}

		return null;
	},
};

export const SalesforceD2CSettings = ( {
	mode,
	uuid,
	config,
}: SettingsComponentProps< SalesforceD2CConfig > ) => {
	const [ clientID, setClientID ] = useState< string >( config?.service_config?.client_id ?? '' );
	const [ clientSecret, setClientSecret ] = useState< string >(
		config?.service_config?.client_secret ?? ''
	);
	const [ domain, setDomain ] = useState< string >( config?.service_config?.domain ?? '' );

	const { onSave } = useDataSources< SalesforceD2CConfig >( false );

	const { state, handleOnChange, validState } = useForm< SalesforceD2CServiceConfig >( {
		initialValues: config?.service_config ?? {
			__version: SERVICE_CONFIG_VERSION,
			enable_blocks: true,
		},
		validationRules,
	} );

	const [ storeOptions, setStoreOptions ] = useState< SelectOption[] >( [
		{
			...defaultSelectOption,
			label: __( 'Auto-filled on successful connection.', 'remote-data-blocks' ),
		},
	] );

	const { stores, fetchingStores, storesError } = useSalesforceD2CAuth(
		state.domain ?? '',
		state.client_id ?? '',
		state.client_secret ?? ''
	);

	const onSaveClick = async () => {
		if ( ! validState ) {
			return;
		}

		const data: SalesforceD2CConfig = {
			service: 'salesforce-d2c',
			service_config: validState,
			uuid: uuid ?? null,
			config_source: ConfigSource.STORAGE,
		};

		return onSave( data, mode );
	};

	const onDomainChange = ( value: string ) => {
		setDomain( value );
		handleOnChange( 'domain', value );
		handleOnChange( 'store_id', '' );
	};

	const onClientIDChange = ( value: string ) => {
		setClientID( value );
		handleOnChange( 'client_id', value );
		handleOnChange( 'store_id', '' );
	};

	const onClientSecretChange = ( value: string ) => {
		setClientSecret( value );
		handleOnChange( 'client_secret', value );
		handleOnChange( 'store_id', '' );
	};

	const onStoreIDChange = ( value: string ) => {
		const selectedStore = stores?.find( store => store.id === value );
		handleOnChange( 'store_id', selectedStore?.id ?? '' );
	};

	const credentialsHelpText = useMemo( () => {
		if ( fetchingStores ) {
			return __( 'Checking credentials...', 'remote-data-blocks' );
		} else if ( storesError ) {
			const errorMessage = storesError.message ?? __( 'Unknown error', 'remote-data-blocks' );
			return getConnectionMessage(
				'error',
				__( 'Failed to generate token using provided credentials: ', 'remote-data-blocks' ) +
					' ' +
					errorMessage
			);
		} else if ( stores ) {
			return getConnectionMessage(
				'success',
				__( 'Credentials are valid. Stores fetched successfully.', 'remote-data-blocks' )
			);
		}
		return __( 'Please provide credentials to connect to Salesforce D2C.', 'remote-data-blocks' );
	}, [ fetchingStores, stores, storesError ] );

	const shouldAllowSubmit = state.store_id && state.store_id !== '';

	useEffect( () => {
		if ( ! stores?.length ) {
			return;
		}

		setStoreOptions( [
			{
				...defaultSelectOption,
				label: __( 'Select a store', 'remote-data-blocks' ),
			},
			...( stores ?? [] ).map( ( { name, id } ) => ( {
				label: name,
				value: id,
			} ) ),
		] );
	}, [ stores ] );

	return (
		<DataSourceForm onSave={ onSaveClick }>
			<DataSourceForm.Setup
				canProceed={ Boolean( stores && stores.length > 0 ) }
				displayName={ state.display_name ?? '' }
				handleOnChange={ handleOnChange }
				heading={ {
					icon: SalesforceCommerceD2CIcon,
					width: '100px',
					height: '75px',
					verticalAlign: 'text-top',
				} }
				inputIcon={ SalesforceCommerceD2CIcon }
				uuid={ uuid }
			>
				<TextControl
					type="text"
					label={ __( 'Domain', 'remote-data-blocks' ) }
					onChange={ onDomainChange }
					value={ domain }
					help={ __(
						'The domain of the Salesforce D2C instance. Example: The domain for https://scomhello123usa456org.lightning.force.com is scomhello123usa456org.'
					) }
					autoComplete="off"
					__next40pxDefaultSize
				/>

				<TextControl
					label={ __( 'Client ID', 'remote-data-blocks' ) }
					onChange={ onClientIDChange }
					value={ clientID }
					help={ credentialsHelpText }
					autoComplete="off"
					__next40pxDefaultSize
				/>

				<TextControl
					label={ __( 'Client Secret', 'remote-data-blocks' ) }
					onChange={ onClientSecretChange }
					value={ clientSecret }
					help={ credentialsHelpText }
					autoComplete="off"
					__next40pxDefaultSize
				/>
			</DataSourceForm.Setup>
			<DataSourceForm.Scope canProceed={ Boolean( shouldAllowSubmit ) }>
				<SelectControl
					id="store_id"
					label={ __( 'Store', 'remote-data-blocks' ) }
					value={ state.store_id ?? '' }
					onChange={ onStoreIDChange }
					options={ storeOptions }
					help={ __( 'Select a store', 'remote-data-blocks' ) }
					disabled={ fetchingStores || ! storeOptions?.length }
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>
			</DataSourceForm.Scope>
			<DataSourceForm.Blocks
				handleOnChange={ handleOnChange }
				hasEnabledBlocks={ Boolean( state.enable_blocks ) }
			/>
		</DataSourceForm>
	);
};
