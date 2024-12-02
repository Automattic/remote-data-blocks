import { SelectControl, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { ChangeEvent } from 'react';

import PasswordInputControl from '@/data-sources/components/PasswordInputControl';
import {
	HTTP_SOURCE_AUTH_TYPE_SELECT_OPTIONS,
	HTTP_SOURCE_ADD_TO_SELECT_OPTIONS,
} from '@/data-sources/constants';
import { HttpAuthFormState } from '@/data-sources/http/types';

interface HttpAuthSettingsInputProps {
	auth: HttpAuthFormState;
	onChange: ( id: string, value: unknown ) => void;
}

export const HttpAuthSettingsInput: React.FC< HttpAuthSettingsInputProps > = ( {
	auth,
	onChange,
} ) => {
	const onSelectChange = (
		value: string,
		extra?: { event?: ChangeEvent< HTMLSelectElement > }
	) => {
		if ( extra?.event ) {
			const { id } = extra.event.target;
			onChange( id, value );
		}
	};

	return (
		<>
			<div className="form-group">
				<SelectControl
					id="authType"
					label={ __( 'Authentication Type', 'remote-data-blocks' ) }
					value={ auth.authType }
					onChange={ onSelectChange }
					options={ HTTP_SOURCE_AUTH_TYPE_SELECT_OPTIONS }
					__next40pxDefaultSize
				/>
			</div>

			{ auth.authType === 'api-key' && (
				<>
					<div className="form-group">
						<SelectControl
							id="authAddTo"
							label={ __( 'Add API Key to', 'remote-data-blocks' ) }
							value={ auth.authAddTo }
							onChange={ onSelectChange }
							options={ HTTP_SOURCE_ADD_TO_SELECT_OPTIONS }
							__next40pxDefaultSize
							help={ __(
								'Add the API key to the header or query parameter. The "Authentication Key Name" field should contain the header name or query param name, and the "Authentication Value" field should contain the value of the header or query parameter.',
								'remote-data-blocks'
							) }
						/>
					</div>

					<div className="form-group">
						<TextControl
							id="authKey"
							label={ __( 'Authentication Key Name', 'remote-data-blocks' ) }
							value={ auth.authKey }
							onChange={ value => onChange( 'authKey', value ) }
							help={ __(
								'The name of the header or query parameter to add the API key to.',
								'remote-data-blocks'
							) }
							__next40pxDefaultSize
						/>
					</div>
				</>
			) }

			{ auth.authType !== 'none' && (
				<div className="form-group">
					<PasswordInputControl
						id="authValue"
						label={ __( 'Authentication Value', 'remote-data-blocks' ) }
						value={ auth.authValue }
						onChange={ value => onChange( 'authValue', value ) }
						__next40pxDefaultSize
						help={ __(
							'The authentication value to use for the HTTP endpoint. When using Basic Auth, this is "username:password" string.',
							'remote-data-blocks'
						) }
					/>
				</div>
			) }
		</>
	);
};
