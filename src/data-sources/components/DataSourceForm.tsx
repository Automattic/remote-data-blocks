import {
	Button,
	DropdownMenu,
	__experimentalInputControl as InputControl,
} from '@wordpress/components';
import { Children, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { cog } from '@wordpress/icons';

import { DataSourceFormActions } from './DataSourceFormActions';

interface DataSourceFormProps {
	children: React.ReactNode;
	icon: React.ReactNode;
	mode: 'add' | 'edit';
	onSave: () => Promise< void >;
	source: string;
}

interface DataSourceFormSetupProps {
	children: React.ReactNode;
	displayName: string;
	handleOnChange: ( key: string, value: string ) => void;
	heading: string | React.ReactNode;
	mode: 'add' | 'edit';
	newUUID: string | null;
	setNewUUID: ( uuid: string | null ) => void;
	uuidFromProps?: string;
}

const DataSourceFormStep = ( {
	children,
	heading,
}: {
	children: React.ReactNode;
	heading: React.ReactNode;
} ) => (
	<fieldset>
		<h2 className="rdb-settings_form-header">{ heading }</h2>
		{ children }
	</fieldset>
);

const DataSourceForm = ( { children, icon, mode, onSave, source }: DataSourceFormProps ) => {
	const [ currentStep, setCurrentStep ] = useState( 1 );

	const steps = Children.toArray( children );

	const stepHeadings = [ 'Setup', 'Scope' ];

	return (
		<>
			<div className="rdb-settings-page_data-source-form-wrapper">
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
								onClick={ () => setCurrentStep( currentStep + 1 ) }
								variant="primary"
								__next40pxDefaultSize
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
								isSaveDisabled={ false }
							/>
						) }
					</>
				) }
				{ mode === 'edit' && (
					<DataSourceFormActions
						onSave={ onSave }
						onCancel={ function (): void {
							throw new Error( 'Function not implemented.' );
						} }
						isSaveDisabled={ false }
					/>
				) }
			</div>
		</>
	);
};

const DataSourceFormSetup = ( {
	children,
	displayName: initialDisplayName,
	handleOnChange,
	mode,
	newUUID,
	setNewUUID,
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
		<DataSourceFormStep heading="Setup">
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

const DataSourceFormScope = ( { children }: { children: React.ReactNode } ) => (
	<DataSourceFormStep heading="Scope">{ children }</DataSourceFormStep>
);

DataSourceForm.Setup = DataSourceFormSetup;
DataSourceForm.Scope = DataSourceFormScope;

export { DataSourceForm };
