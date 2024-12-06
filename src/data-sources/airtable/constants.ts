export const STRING_TYPES = Object.freeze(
	new Set( [
		'singleLineText',
		'multilineText',
		'email',
		'phoneNumber',
		'richText',
		'barcode',
		'singleSelect',
		'multipleSelect',
		'date',
		'dateTime',
		'lastModifiedTime',
		'createdTime',
	] )
);

export const NUMBER_TYPES = Object.freeze(
	new Set( [ 'number', 'autoNumber', 'rating', 'duration', 'count' ] )
);

export const SUPPORTED_AIRTABLE_TYPES = Object.freeze( [
	// String types
	'singleLineText',
	'multilineText',
	'email',
	'phoneNumber',
	'richText',
	'barcode',
	'singleSelect',
	'multipleSelect',
	'date',
	'dateTime',
	'lastModifiedTime',
	'createdTime',
	// Number types
	'number',
	'autoNumber',
	'rating',
	'duration',
	'count',
	// Other types
	'url',
	'button',
	'currency',
	'checkbox',
	'multipleAttachments',
	'singleCollaborator',
	'multipleCollaborator',
	'formula',
	'rollup',
	'lookup',
] );
