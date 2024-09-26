import { StringIdName } from '@/types/common';

export interface AirtableBase {
	id: string;
	name: string;
	permissionLevel: 'none' | 'read' | 'comment' | 'edit' | 'create';
}

export interface AirtableBasesResult {
	offset?: string;
	bases: AirtableBase[];
}

export interface AirtableBaseSchema {
	tables: AirtableTable[];
}

export type AirtableFormState = {
	access_token: string;
	base: StringIdName | null;
	table: StringIdName | null;
	table_fields: Set< string >;
	slug: string;
};

export interface AirtableTable {
	id: string;
	name: string;
	primaryFieldId: string;
	fields: AirtableField[];
	views: AirtableView[];
	description: string | null;
	createTime: string;
	syncStatus: 'complete' | 'pending';
}

interface AirtableField {
	id: string;
	name: string;
	type: string;
	description: string | null;
	options?: {
		[ key: string ]: unknown;
	};
}

interface AirtableView {
	id: string;
	name: string;
	type: 'grid' | 'form' | 'calendar' | 'gallery' | 'kanban' | 'timeline' | 'block';
}

export interface AirtableApiArgs {
	token: string;
}

export interface AirtableBaseOption {
	id: string;
	name: string;
}
