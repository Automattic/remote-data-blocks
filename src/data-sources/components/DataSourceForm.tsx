import {
	Button,
	DropdownMenu,
	ExternalLink,
	Icon,
	IconType,
	VisuallyHidden,
	__experimentalInputControl as InputControl,
} from '@wordpress/components';
import { Children, isValidElement, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { cog, lockSmall } from '@wordpress/icons';

import { DataSourceFormActions } from './DataSourceFormActions';

interface DataSourceFormProps {
	children: React.ReactNode;
	icon: IconType;
	mode: 'add' | 'edit';
	onSave: () => Promise< void >;
	source: string;
}

interface DataSourceFormSetupProps {
	children: React.ReactNode;
	canProceed: boolean;
	displayName: string;
	handleOnChange: ( key: string, value: string ) => void;
	icon: IconType;
	mode: 'add' | 'edit';
	newUUID: string | null;
	setNewUUID: ( uuid: string | null ) => void;
	source: string;
	uuidFromProps?: string;
}

const DataSourceFormStep = ( {
	children,
	heading,
}: {
	children: React.ReactNode;
	heading: React.ReactNode;
} ) => (
	<>
		<h2 className="rdb-settings_form-header">{ heading }</h2>
		<fieldset className="rdb-settings_form-fields">{ children }</fieldset>
	</>
);

const DataSourceForm = ( { children, icon, mode, onSave, source }: DataSourceFormProps ) => {
	const [ currentStep, setCurrentStep ] = useState( 1 );

	const steps = Children.toArray( children );

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
			<div className={ `rdb-settings-page_data-source-${ mode }-form` }>
				{ mode === 'add' && (
					<>
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
						<form className="rdb-settings-page_data-source-form">{ steps[ currentStep - 1 ] }</form>
					</>
				) }
				{ mode === 'edit' && (
					<form className="rdb-settings-page_data-source-form">{ steps.map( step => step ) }</form>
				) }
			</div>

			<div className="rdb-settings-page_data-source-form-footer">
				{ mode === 'add' && currentStep === 1 && (
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
					{ mode === 'add' && (
						<>
							{ currentStep === 1 && (
								<Button
									onClick={ () => console.log( 'Go to main screen' ) }
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
					) }
					{ mode === 'edit' && (
						<>
							<Button
								onClick={ () => console.log( 'Go to main screen' ) }
								variant="secondary"
								__next40pxDefaultSize
							>
								Cancel
							</Button>
							<DataSourceFormActions
								onSave={ onSave }
								onCancel={ function (): void {
									throw new Error( 'Function not implemented.' );
								} }
								isSaveDisabled={ false }
							/>
						</>
					) }
				</div>
			</div>
		</>
	);
};

const DataSourceFormSetup = ( {
	children,
	displayName: initialDisplayName,
	handleOnChange,
	icon,
	mode,
	newUUID,
	setNewUUID,
	source,
	uuidFromProps,
}: DataSourceFormSetupProps ) => {
	const [ displayName, setDisplayName ] = useState( initialDisplayName );
	const [ editUUID, setEditUUID ] = useState( false );

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
				<>
					{ __( 'Connect with ', 'remote-data-blocks' ) }

					<Icon icon={ icon } style={ { width: '113.81px', height: '25px', marginLeft: '4px' } } />
					<VisuallyHidden>{ __( source, 'remote-data-blocks' ) }</VisuallyHidden>
				</>
			}
		>
			{ mode === 'edit' && (
				<>
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
					/>
					{ mode === 'edit' && editUUID && (
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
					__next40pxDefaultSize
				/>
			</div>
			{ children }
		</DataSourceFormStep>
	);
};

const DataSourceFormScope = ( {
	children,
}: {
	canProceed: boolean;
	children: React.ReactNode;
} ) => <DataSourceFormStep heading="Scope">{ children }</DataSourceFormStep>;

DataSourceForm.Setup = DataSourceFormSetup;
DataSourceForm.Scope = DataSourceFormScope;

export { DataSourceForm };
