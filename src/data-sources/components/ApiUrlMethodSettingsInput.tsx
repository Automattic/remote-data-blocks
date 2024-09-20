import { SelectControl, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { REST_API_SOURCE_METHOD_SELECT_OPTIONS, HTTP_METHODS } from '@/data-sources/constants';

interface ApiUrlMethodSettingsInputProps {
	url: string;
	method: ( typeof HTTP_METHODS )[ number ];
	onChange: ( id: string, value: unknown ) => void;
}

export const ApiUrlMethodSettingsInput: React.FC< ApiUrlMethodSettingsInputProps > = ( {
	url,
	method,
	onChange,
} ) => {
	return (
		<>
			<div className="form-group">
				<TextControl
					type="url"
					id="url"
					label={ __( 'URL', 'remote-data-blocks' ) }
					value={ url }
					onChange={ value => onChange( 'url', value ) }
					autoComplete="off"
					__next40pxDefaultSize
					help={ __( 'The URL for the REST API endpoint.', 'remote-data-blocks' ) }
				/>
			</div>

			<div className="form-group">
				<SelectControl
					id="method"
					label={ __( 'Method', 'remote-data-blocks' ) }
					value={ method }
					onChange={ value => onChange( 'method', value ) }
					options={ REST_API_SOURCE_METHOD_SELECT_OPTIONS }
				/>
			</div>
		</>
	);
};
