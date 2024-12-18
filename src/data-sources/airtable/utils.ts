import {
	AIRTABLE_STRING_TYPES,
	AIRTABLE_NUMBER_TYPES,
	AIRTABLE_USER_TYPES,
} from '@/data-sources/airtable/constants';
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

	if ( AIRTABLE_STRING_TYPES.has( field.type ) ) {
		return { ...baseField, type: 'string' };
	}

	if ( AIRTABLE_NUMBER_TYPES.has( field.type ) ) {
		return { ...baseField, type: 'number' };
	}

	if ( AIRTABLE_USER_TYPES.has( field.type ) ) {
		return { ...baseField, path: `$.fields["${ field.name }"].name`, type: 'string' };
	}

	switch ( field.type ) {
		case 'currency':
			return {
				...baseField,
				type: 'currency',
				prefix: field.options?.symbol,
			};

		case 'checkbox':
			return { ...baseField, type: 'boolean' };

		case 'multipleSelects':
			return {
				...baseField,
				path: `$.fields["${ field.name }"][*]`,
				type: 'string',
			};

		case 'multipleRecordLinks':
			return {
				...baseField,
				path: `$.fields["${ field.name }"][*].id`,
				type: 'string',
			};

		case 'multipleAttachments':
			return {
				...baseField,
				path: `$.fields["${ field.name }"][0].url`,
				type: 'image_url',
			};

		case 'multipleCollaborators':
			return {
				...baseField,
				path: `$.fields["${ field.name }"][*].name`,
				type: 'string',
			};

		case 'formula':
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
