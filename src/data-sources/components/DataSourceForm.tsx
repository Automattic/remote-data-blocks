import {
	Button,
	ExternalLink,
	Icon,
	IconType,
	VisuallyHidden,
	__experimentalInputControl as InputControl,
	__experimentalInputControlPrefixWrapper as InputControlPrefixWrapper,
	__experimentalSpacer as Spacer,
} from '@wordpress/components';
import { Children, createPortal, isValidElement, useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { lockSmall } from '@wordpress/icons';

import { DataSourceFormActions } from './DataSourceFormActions';
import { useDataSources } from '../hooks/useDataSources';
import { ModalWithButtonTrigger } from '@/blocks/remote-data-container/components/modals/BaseModal';
import { useModalState } from '@/blocks/remote-data-container/hooks/useModalState';
import { useSettingsContext } from '@/settings/hooks/useSettingsNav';

interface DataSourceFormProps {
	children: React.ReactNode;
	onSave: () => Promise< void >;
}

interface DataSourceFormSetupProps {
	children: React.ReactNode;
	canProceed: boolean;
	displayName: string;
	handleOnChange: ( key: string, value: string ) => void;
	heading:
		| {
				label: string;
				icon?: never;
				width?: never;
				height?: never;
				verticalAlign?: never;
		  }
		| {
				label?: never;
				icon: IconType;
				width: string;
				height: string;
				verticalAlign?: string;
		  };
	inputIcon: IconType;
	newUUID: string | null;
	setNewUUID: ( uuid: string | null ) => void;
	uuidFromProps?: string;
}

const DataSourceFormStep = ( {
	children,
	heading,
	subheading,
}: {
	children: React.ReactNode;
	heading: React.ReactNode;
	subheading?: React.ReactNode;
} ) => (
	<>
		<h2 className="rdb-settings_form-heading">{ heading }</h2>
		<h3 className="rdb-settings_form-subheading">{ subheading }</h3>
		<fieldset className="rdb-settings_form-fields">{ children }</fieldset>
	</>
);

const DataSourceForm = ( { children, onSave }: DataSourceFormProps ) => {
	const [ currentStep, setCurrentStep ] = useState( 1 );
	const { goToMainScreen, screen } = useSettingsContext();

	const { checkDisplayNameConflict } = useDataSources();

	const steps = Children.toArray( children );
	const singleStep = steps.length === 1 || screen === 'editDataSource';

	const stepHeadings = [ 'Setup', 'Scope' ];

	const canProceedToNextStep = (): boolean => {
		const step = steps[ currentStep - 1 ];
		if (
			isValidElement< { canProceed?: boolean; displayName: string; uuidFromProps: string } >( step )
		) {
			const { displayName, uuidFromProps } = step.props;

			if ( currentStep === 1 ) {
				return (
					Boolean( step.props?.canProceed ) &&
					checkDisplayNameConflict( displayName, uuidFromProps )
				);
			}
			return Boolean( step.props?.canProceed );
		}
		return false;
	};

	const handleNextStep = () => {
		if ( canProceedToNextStep() ) {
			setCurrentStep( currentStep + 1 );
		}
	};

	return (
		<>
			<div
				className={ `rdb-settings-page_data-source-${
					singleStep ? 'single-step' : 'multi-step'
				}-form rdb-settings-page_data-source-${
					screen === 'addDataSource' ? 'add' : 'edit'
				}-form` }
			>
				{ ! singleStep && (
					<nav className="rdb-settings_form-steps" aria-label="Setup form steps">
						<ol>
							{ stepHeadings.map( ( label, index ) => {
								const stepNumber = index + 1;
								return (
									<li
										key={ stepNumber }
										aria-current={ currentStep === stepNumber ? 'step' : undefined }
										className={ currentStep === stepNumber ? 'current-step' : '' }
									>
										{ label }
									</li>
								);
							} ) }
						</ol>
					</nav>
				) }
				<form className="rdb-settings-page_data-source-form">
					{ singleStep ? steps.map( step => step ) : steps[ currentStep - 1 ] }
				</form>
				{ screen === 'editDataSource' && (
					<>
						{ createPortal(
							<DataSourceFormActions
								onSave={ onSave }
								isSaveDisabled={ ! canProceedToNextStep() }
							/>,
							document.getElementById( 'rdb-settings-page-form-save-button' ) ||
								document.createElement( 'div' )
						) }
					</>
				) }
			</div>
			{ screen === 'addDataSource' && (
				<div className="rdb-settings-page_data-source-form-footer">
					{ currentStep === 1 && (
						<div className="rdb-settings-page_data-source-form-setup-info">
							<Icon icon={ lockSmall } />
							<p>
								{ __(
									'Connecting to an external source will not store the data. We issue queries to the database, but all your data stays with the provider. '
								) }
								<ExternalLink href="https://remotedatablocks.com/">
									{ __( 'Learn more', 'remote-data-blocks' ) }
								</ExternalLink>
							</p>
						</div>
					) }
					<div className="rdb-settings-page_data-source-form-setup-actions">
						<>
							{ currentStep === 1 && (
								<Button
									onClick={ () => goToMainScreen() }
									variant="secondary"
									__next40pxDefaultSize
								>
									Cancel
								</Button>
							) }
							{ currentStep > 1 && (
								<Button
									onClick={ () => setCurrentStep( currentStep - 1 ) }
									variant="secondary"
									__next40pxDefaultSize
								>
									Go back
								</Button>
							) }
							{ currentStep < steps.length && (
								<Button
									onClick={ handleNextStep }
									variant="primary"
									__next40pxDefaultSize
									disabled={ ! canProceedToNextStep() }
								>
									Continue
								</Button>
							) }
							{ currentStep === steps.length && (
								<DataSourceFormActions
									onSave={ onSave }
									isSaveDisabled={ ! canProceedToNextStep() }
								/>
							) }
						</>
					</div>
				</div>
			) }
		</>
	);
};

const DataSourceFormSetup = ( {
	children,
	canProceed,
	displayName: initialDisplayName,
	handleOnChange,
	heading,
	inputIcon,
	newUUID,
	setNewUUID,
	uuidFromProps,
}: DataSourceFormSetupProps ) => {
	const { close, isOpen, open } = useModalState();
	const { screen, service } = useSettingsContext();
	const { checkDisplayNameConflict } = useDataSources();

	const [ displayName, setDisplayName ] = useState( initialDisplayName );
	const [ errors, setErrors ] = useState< Record< string, string > >( {} );

	const { icon, height, label, width, verticalAlign } = heading;

	const onDisplayNameChange = ( displayNameInput: string | undefined ) => {
		setErrors( {} );
		const sanitizedDisplayName = displayNameInput
			?.toString()
			.trim()
			.replace( /[^a-zA-Z0-9-_ ]/g, '' );
		setDisplayName( sanitizedDisplayName ?? '' );
		handleOnChange( 'display_name', sanitizedDisplayName ?? '' );
	};

	const isValidUUIDv4 = ( uuid: string ) => {
		const uuidv4Regex = /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
		return uuidv4Regex.test( uuid );
	};

	const validateDisplayName = () => {
		const hasConflict = ! checkDisplayNameConflict( displayName, uuidFromProps ?? '' );

		if ( ! displayName.trim() ) {
			setErrors( {
				displayName: __( 'Please provide a name for your data source.', 'remote-data-blocks' ),
			} );
		} else if ( hasConflict ) {
			setErrors( {
				displayName: sprintf(
					__(
						'Data source "%s" already exists. Please choose another name.',
						'remote-data-blocks'
					),
					displayName
				),
			} );
		} else {
			setErrors( {} );
		}
	};

	useEffect( () => {
		if ( canProceed ) {
			validateDisplayName();
		}
	}, [ canProceed, displayName ] );

	return (
		<DataSourceFormStep
			heading={
				screen === 'addDataSource' && service ? (
					<span style={ { marginBottom: '48px' } }>
						{ label ? (
							__( label, 'remote-data-blocks' )
						) : (
							<>
								{ __( 'Connect with ', 'remote-data-blocks' ) }
								<Icon
									icon={ icon }
									style={ {
										width,
										height,
										marginLeft: '4px',
										verticalAlign: verticalAlign ?? 'text-bottom',
									} }
								/>
								<VisuallyHidden>{ __( service, 'remote-data-blocks' ) }</VisuallyHidden>
							</>
						) }
					</span>
				) : (
					<>{ __( 'Setup' ) }</>
				)
			}
			subheading={
				screen === 'editDataSource'
					? __( 'Manage the source’s visibility and connection details.', 'remote-data-blocks' )
					: undefined
			}
		>
			<InputControl
				className={ `rdb-settings-page_data-source-form-input ${
					errors.displayName ? 'has-error' : ''
				}   ` }
				help={
					<>
						<span>
							{ errors.displayName
								? errors.displayName
								: __( 'Only visible to you and other site managers. ', 'remote-data-blocks' ) }
						</span>
						{ screen === 'editDataSource' && ! errors.displayName && (
							<ModalWithButtonTrigger
								buttonText={ __( 'Edit identifier', 'remote-data-blocks' ) }
								isOpen={ isOpen }
								onClose={ close }
								onOpen={ open }
								title={ __( 'Edit identifier', 'remote-data-blocks' ) }
								buttonVariant="link"
								size="medium"
							>
								<form
									onSubmit={ event => {
										event.preventDefault();
										if ( newUUID && isValidUUIDv4( newUUID ) ) {
											isValidUUIDv4( newUUID );
											handleOnChange( 'uuid', newUUID ?? '' );
											setErrors( {} );
											close();
										} else {
											setErrors( {
												uuid: __(
													'Please use valid UUIDv4 formatting to save changes.',
													'remote-data-blocks'
												),
											} );
										}
									} }
								>
									<InputControl
										className={ `rdb-settings-page_data-source-form-input ${
											errors.uuid ? 'has-error' : ''
										}   ` }
										label={ __( 'UUID', 'remote-data-blocks' ) }
										value={ newUUID ?? '' }
										onChange={ ( uuid?: string ) => setNewUUID( uuid ?? null ) }
										placeholcomponents-base-control__helpder={ uuidFromProps }
										__next40pxDefaultSize
										help={
											<span>
												{ errors.uuid
													? errors.uuid
													: __(
															'Unique identifier allows you to reference this data source in code.',
															'remote-data-blocks'
													  ) }
											</span>
										}
									/>
									<Spacer marginBottom={ 8 } />
									<div className="rdb-settings-page_data-source-form-uuid-actions">
										<Button
											onClick={ () => {
												setNewUUID( uuidFromProps ?? null );
												setErrors( {} );
												close();
											} }
											variant="tertiary"
											__next40pxDefaultSize
										>
											Cancel
										</Button>
										<Button type="submit" variant="primary">
											Save
										</Button>
									</div>
								</form>
							</ModalWithButtonTrigger>
						) }
					</>
				}
				label={ __( 'Data Source Name' ) }
				onChange={ onDisplayNameChange }
				onBlur={ validateDisplayName }
				value={ displayName }
				prefix={
					screen === 'editDataSource' ? (
						<InputControlPrefixWrapper style={ { paddingRight: '4px' } }>
							<Icon icon={ inputIcon } style={ { verticalAlign: 'text-bottom' } } />
						</InputControlPrefixWrapper>
					) : null
				}
				required
				__next40pxDefaultSize
			/>

			{ children }
		</DataSourceFormStep>
	);
};

const DataSourceFormScope = ( {
	children,
	...props
}: {
	children: React.ReactNode;
	canProceed: boolean;
} ) => {
	const { service } = useSettingsContext();
	return (
		<DataSourceFormStep
			heading="Scope"
			subheading={ __(
				`Choose what data should be pulled from ${ service ?? 'your data source' } to your site.`
			) }
			{ ...props }
		>
			{ children }
		</DataSourceFormStep>
	);
};

DataSourceForm.Setup = DataSourceFormSetup;
DataSourceForm.Scope = DataSourceFormScope;

export { DataSourceForm };
