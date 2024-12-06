import {
	Button,
	DropdownMenu,
	ExternalLink,
	Icon,
	IconType,
	VisuallyHidden,
	__experimentalInputControl as InputControl,
	__experimentalInputControlPrefixWrapper as InputControlPrefixWrapper,
} from '@wordpress/components';
import { Children, createPortal, isValidElement, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { cog, lockSmall } from '@wordpress/icons';

import { DataSourceFormActions } from './DataSourceFormActions';
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

	const steps = Children.toArray( children );
	const singleStep = steps.length === 1 || screen === 'editDataSource';

	const stepHeadings = [ 'Setup', 'Scope' ];

	const canProceedToNextStep = (): boolean => {
		const step = steps[ currentStep - 1 ];
		return isValidElement< { canProceed?: boolean } >( step )
			? Boolean( step.props?.canProceed )
			: false;
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
								onCancel={ function (): void {
									throw new Error( 'Function not implemented.' );
								} }
								isSaveDisabled={ false }
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
									onCancel={ function (): void {
										throw new Error( 'Function not implemented.' );
									} }
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
	displayName: initialDisplayName,
	handleOnChange,
	heading,
	inputIcon,
	newUUID,
	setNewUUID,
	uuidFromProps,
}: DataSourceFormSetupProps ) => {
	const [ displayName, setDisplayName ] = useState( initialDisplayName );
	const [ editUUID, setEditUUID ] = useState( false );

	const { screen, service } = useSettingsContext();
	const { icon, height, label, width, verticalAlign } = heading;

	const onUUIDChange = ( uuid: string | undefined ) => {
		setNewUUID( uuid ?? null );
		handleOnChange( 'uuid', uuid ?? '' );
	};

	const onDisplayNameChange = ( displayNameInput: string | undefined ) => {
		const sanitizedDisplayName = displayNameInput
			?.toString()
			.trim()
			.replace( /[^a-zA-Z0-9-_ ]/g, '' );
		setDisplayName( sanitizedDisplayName ?? '' );
		handleOnChange( 'display_name', sanitizedDisplayName ?? '' );
	};

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
		>
			{ screen === 'editDataSource' && (
				<>
					{ createPortal(
						<DropdownMenu
							controls={ [
								{
									title: __(
										editUUID
											? __( 'Hide UUID', 'remote-data-blocks' )
											: __( 'Edit UUID', 'remote-data-blocks' )
									),
									onClick: () => setEditUUID( ! editUUID ),
								},
							] }
							icon={ cog }
							label={ __( 'Additional Settings' ) }
						/>,
						document.getElementById( 'rdb-settings-page-form-settings' ) ||
							document.createElement( 'div' )
					) }

					{ editUUID && (
						<InputControl
							label={ __( 'UUID', 'remote-data-blocks' ) }
							value={ newUUID ?? '' }
							onChange={ onUUIDChange }
							placeholder={ uuidFromProps }
							__next40pxDefaultSize
							help={ __( 'Unique identifier for this data source.', 'remote-data-blocks' ) }
						/>
					) }
				</>
			) }
			<div className="form-group">
				<InputControl
					help={ __( 'Only visible to you and other site managers.', 'remote-data-blocks' ) }
					label={ __( 'Data Source Name' ) }
					onChange={ onDisplayNameChange }
					value={ displayName }
					prefix={
						screen === 'editDataSource' ? (
							<InputControlPrefixWrapper style={ { paddingRight: '4px' } }>
								<Icon icon={ inputIcon } />
							</InputControlPrefixWrapper>
						) : null
					}
					__next40pxDefaultSize
				/>
			</div>
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
