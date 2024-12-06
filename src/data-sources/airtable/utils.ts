import { STRING_TYPES, NUMBER_TYPES } from '@/data-sources/airtable/constants';
import { AirtableField } from '@/data-sources/airtable/types';
import { AirtableOutputQueryMappingValue } from '@/data-sources/types';

export const getAirtableOutputQueryMappingValue = (
	field: AirtableField
): AirtableOutputQueryMappingValue => {
	const baseField = {
		path: `$.fields["${ field.name }"]`,
		name: field.name,
		key: field.name,
	};

	if ( STRING_TYPES.has( field.type ) ) {
		return { ...baseField, type: 'string' };
	}

	if ( NUMBER_TYPES.has( field.type ) ) {
		return { ...baseField, type: 'number' };
	}

	switch ( field.type ) {
		case 'url':
		case 'button':
			return { ...baseField, type: 'button_url' };

		case 'currency':
			return { ...baseField, type: 'price' };

		case 'checkbox':
			return { ...baseField, type: 'boolean' };

		case 'multipleAttachments':
			return {
				path: `$.fields["${ field.name }"][0].url`,
				name: `${ field.name } URL`,
				key: `${ field.name }_url`,
				type: 'image_url',
			};

		case 'singleCollaborator':
		case 'multipleCollaborator':
			return {
				path: `$.fields["${ field.name }"].name`,
				name: `${ field.name } Name`,
				key: `${ field.name }_name`,
				type: 'string',
			};

		case 'formula':
		case 'rollup':
		case 'lookup':
			if ( field.options?.result?.type ) {
				return getAirtableOutputQueryMappingValue( {
					...field,
					type: field.options.result.type,
				} );
			}
			return { ...baseField, type: 'string' };

		default:
			return { ...baseField, type: 'string' };
	}
};
