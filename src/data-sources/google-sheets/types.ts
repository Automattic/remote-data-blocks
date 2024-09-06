import { GoogleSheetsConfig } from '@/data-sources/types';
import { StringIdName } from '@/types/common';

export type GoogleSheetsFormState = NullableKeys<
	Omit< GoogleSheetsConfig, 'service' | 'uuid' | 'sheet' | 'credentials' >,
	'spreadsheet'
> & {
	sheet: StringIdName | null;
	credentials: string;
};
