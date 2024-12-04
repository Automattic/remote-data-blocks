import {
	DropdownMenu,
	__experimentalHeading as Heading,
	__experimentalInputControl as InputControl,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { cog } from '@wordpress/icons';

type DataSourceFormProps = React.FormHTMLAttributes< HTMLFormElement > & {
	children: React.ReactNode;
	displayName: string;
	handleOnChange: ( key: string, value: string ) => void;
	heading: string | React.ReactNode;
	mode: 'add' | 'edit';
	newUUID: string | null;
	setNewUUID: ( uuid: string | null ) => void;
	uuidFromProps?: string;
};

export const DataSourceForm = ( {
	children,
	displayName: initialDisplayName,
	handleOnChange,
	heading,
	mode,
	newUUID,
	setNewUUID,
	uuidFromProps,
}: DataSourceFormProps ) => {
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
		<form className="rdb-settings-page_data-source-form">
			<div className="rdb-settings-page_data-source-form_header">
				<Heading size={ 24 }>{ heading }</Heading>
				{ mode === 'edit' && (
					<DropdownMenu
						controls={ [
							{
								title: __( editUUID ? 'Hide UUID' : 'Edit UUID' ),
								onClick: () => setEditUUID( ! editUUID ),
							},
						] }
						icon={ cog }
						label={ __( 'Additional Settings' ) }
					/>
				) }
			</div>
			{ mode === 'edit' && editUUID && (
				<div className="form-group">
					<InputControl
						label={ __( 'UUID', 'remote-data-blocks' ) }
						value={ newUUID ?? '' }
						onChange={ onUUIDChange }
						placeholder={ uuidFromProps }
						__next40pxDefaultSize
						help={ __( 'Unique identifier for this data source.', 'remote-data-blocks' ) }
					/>
				</div>
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
		</form>
	);
};
