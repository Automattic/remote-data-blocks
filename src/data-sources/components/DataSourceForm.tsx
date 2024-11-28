import {
	__experimentalHeading as Heading,
	__experimentalInputControl as InputControl,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

type DataSourceFormProps = React.FormHTMLAttributes< HTMLFormElement > & {
	children: React.ReactNode;
	handleOnChange: ( key: string, value: string ) => void;
	heading: string | React.ReactNode;
};

export const DataSourceForm = ( { children, handleOnChange, heading }: DataSourceFormProps ) => {
	const [ displayName, setDisplayName ] = useState( '' );

	const onDisplayNameChange = ( displayNameInput: string | undefined ) => {
		setDisplayName( displayNameInput ?? '' );
		handleOnChange( 'display_name', displayNameInput ?? '' );
	};

	return (
		<form className="rdb-settings-page_data-source-form">
			<Heading size={ 24 }>{ heading }</Heading>
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
