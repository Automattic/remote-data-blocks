export const AIRTABLE_STRING_TYPES = Object.freeze(
	new Set( [
		'singleLineText',
		'multilineText',
		'email',
		'phoneNumber',
		'richText',
		'barcode',
		'singleSelect',
		'date',
		'dateTime',
		'lastModifiedTime',
		'createdTime',
		'multipleRecordLinks',
		'rollup',
		'externalSyncSource',
	] )
);

export const AIRTABLE_NUMBER_TYPES = Object.freeze(
	new Set( [ 'number', 'autoNumber', 'rating', 'duration', 'count', 'percent' ] )
);

export const AIRTABLE_USER_TYPES = Object.freeze(
	new Set( [ 'createdBy', 'lastModifiedBy', 'singleCollaborator' ] )
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
	'multipleSelects',
	'date',
	'dateTime',
	'lastModifiedTime',
	'createdTime',
	'multipleRecordLinks',
	'rollup',
	'externalSyncSource',
	// Number types
	'number',
	'autoNumber',
	'rating',
	'duration',
	'count',
	'percent',
	// User types
	'createdBy',
	'lastModifiedBy',
	'singleCollaborator',
	// Other types
	'multipleCollaborator',
	'url',
	'button',
	'currency',
	'checkbox',
	'multipleAttachments',
	'formula',
	'lookup',
] );
