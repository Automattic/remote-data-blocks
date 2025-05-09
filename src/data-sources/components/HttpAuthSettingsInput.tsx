import { SelectControl, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { ChangeEvent } from 'react';

import PasswordInputControl from '@/data-sources/components/PasswordInputControl';
import {
	HTTP_SOURCE_AUTH_TYPE_HELP_TEXT,
	HTTP_SOURCE_AUTH_TYPE_SELECT_OPTIONS,
	HTTP_SOURCE_AUTH_VALUE_HELP_TEXT,
	HTTP_SOURCE_AUTH_VALUE_LABELS,
	HTTP_SOURCE_ADD_TO_SELECT_OPTIONS,
} from '@/data-sources/constants';
import { HttpConfig } from '@/data-sources/types';

interface HttpAuthSettingsInputProps {
	auth: HttpConfig[ 'service_config' ][ 'auth' ];
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
			<SelectControl
				id="type"
				label={ __( 'Authentication Type', 'remote-data-blocks' ) }
				value={ auth?.type ?? 'none' }
				onChange={ onSelectChange }
				options={ HTTP_SOURCE_AUTH_TYPE_SELECT_OPTIONS }
				help={ HTTP_SOURCE_AUTH_TYPE_HELP_TEXT[ auth?.type ?? 'none' ] }
				__next40pxDefaultSize
				__nextHasNoMarginBottom
			/>

			{ auth?.type === 'api-key' && (
				<>
					<SelectControl
						id="add_to"
						label={ __( 'Directive', 'remote-data-blocks' ) }
						value={ auth.add_to ?? 'header' }
						onChange={ onSelectChange }
						options={ HTTP_SOURCE_ADD_TO_SELECT_OPTIONS }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>

					<TextControl
						id="key"
						label={ __( 'Name', 'remote-data-blocks' ) }
						value={ auth.key ?? '' }
						onChange={ value => onChange( 'key', value ) }
						help={ __( 'The name of the HTTP header or query parameter.', 'remote-data-blocks' ) }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
				</>
			) }
			{ auth?.type !== 'none' && (
				<PasswordInputControl
					id="value"
					label={ HTTP_SOURCE_AUTH_VALUE_LABELS[ auth?.type ?? 'none' ] }
					value={ auth?.value ?? '' }
					help={ HTTP_SOURCE_AUTH_VALUE_HELP_TEXT[ auth?.type ?? 'none' ] }
					onChange={ value => onChange( 'value', value ) }
					__next40pxDefaultSize
				/>
			) }
		</>
	);
};
