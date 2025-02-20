import { SelectControl, TextControl } from '@wordpress/components';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { DataSourceForm } from '@/data-sources/components/DataSourceForm';
import { useDataSources } from '@/data-sources/hooks/useDataSources';
import { useSalesforceD2CWebstoresOptions } from '@/data-sources/hooks/useSalesforceD2CAPI';
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

	const { token, fetchingToken, tokenError } = useSalesforceD2CAuth(
		state.domain ?? '',
		state.client_id ?? '',
		state.client_secret ?? ''
	);

	const { webstores, isLoadingWebstores, errorWebstores } = useSalesforceD2CWebstoresOptions(
		token,
		state.domain ?? ''
	);

	const onSaveClick = async () => {
		if ( ! validState ) {
			return;
		}

		const data: SalesforceD2CConfig = {
			service: 'salesforce-d2c',
			service_config: validState,
			uuid: uuid ?? null,
		};

		return onSave( data, mode );
	};

	const onClientIDChange = ( value: string ) => {
		setClientID( value );
		handleOnChange( 'client_id', value );
		handleOnChange( 'store_id', '' );
		handleOnChange( 'stores', [] );
	};

	const onClientSecretChange = ( value: string ) => {
		setClientSecret( value );
		handleOnChange( 'client_secret', value );
		handleOnChange( 'store_id', '' );
		handleOnChange( 'stores', [] );
	};

	const onStoreIDChange = ( value: string ) => {
		const selectedStore = webstores?.find( store => store.value === value );
		handleOnChange( 'store_id', selectedStore?.value ?? '' );
		handleOnChange( 'stores', [] );
	};

	const onDomainChange = ( value: string ) => {
		setDomain( value );
		handleOnChange( 'client_id', '' );
		handleOnChange( 'client_secret', '' );
		handleOnChange( 'store_id', '' );
		handleOnChange( 'stores', [] );
	};

	const credentialsHelpText = useMemo( () => {
		if ( fetchingToken ) {
			return __( 'Checking credentials...', 'remote-data-blocks' );
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
		return __( 'Please provide credentials to connect to Salesforce D2C.', 'remote-data-blocks' );
	}, [ fetchingToken, token, tokenError ] );

	const shouldAllowSubmit = state.store_id && state.store_id !== '';

	const storeHelpText = useMemo( () => {
		if ( token ) {
			if ( errorWebstores ) {
				const errorMessage = errorWebstores?.message ?? __( 'Unknown error', 'remote-data-blocks' );
				return __( 'Failed to fetch stores.', 'remote-data-blocks' ) + ' ' + errorMessage;
			} else if ( isLoadingWebstores ) {
				return __( 'Fetching stores...', 'remote-data-blocks' );
			} else if ( webstores?.length === 0 ) {
				return __( 'No stores found', 'remote-data-blocks' );
			}
		}

		return __( 'Select a store.', 'remote-data-blocks' );
	}, [ token, errorWebstores, isLoadingWebstores, webstores ] );

	useEffect( () => {
		if ( ! webstores?.length ) {
			return;
		}

		setStoreOptions( [
			{
				...defaultSelectOption,
				label: __( 'Select a store', 'remote-data-blocks' ),
			},
			...( webstores ?? [] ).map( ( { label, value } ) => ( { label, value } ) ),
		] );
	}, [ webstores ] );

	return (
		<DataSourceForm onSave={ onSaveClick }>
			<DataSourceForm.Setup
				canProceed={ Boolean( token ) }
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
					help={ storeHelpText }
					disabled={ fetchingToken || ! storeOptions?.length }
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>
			</DataSourceForm.Scope>
			<DataSourceForm.Blocks
				handleOnChange={ handleOnChange }
				hasEnabledBlocks={ state.enable_blocks ?? true }
			/>
		</DataSourceForm>
	);
};
