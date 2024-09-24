import { __, sprintf } from '@wordpress/i18n';

import { GoogleSpreadsheet, GoogleDriveFileList, GoogleDriveFile } from '@/types/google';
import { SelectOption } from '@/types/input';

export class GoogleApi {
	private static SHEETS_BASE_URL = 'https://sheets.googleapis.com/v4';
	private static DRIVE_BASE_URL = 'https://www.googleapis.com/drive/v3';

	constructor( private token: string | null ) {}

	private getAuthHeaders() {
		if ( ! this.token ) {
			throw new Error( 'No token provided' );
		}

		return {
			Authorization: `Bearer ${ this.token }`,
		};
	}

	private async fetchApi< T >( url: string, options: RequestInit = {} ): Promise< T > {
		const response = await fetch( url, {
			...options,
			headers: {
				...( options.headers ?? {} ),
				...this.getAuthHeaders(),
			},
		} );

		if ( ! response.ok ) {
			const errorText = `${ response.status } - ${ await response.text() }`;
			throw new Error( `[Google API] ${ sprintf( __( 'Error: %s' ), errorText ) }` );
		}

		return response.json() as Promise< T >;
	}

	private async getSpreadsheetList(): Promise< GoogleDriveFile[] > {
		const spreadsheetsMimeType = 'application/vnd.google-apps.spreadsheet';
		const query = `mimeType='${ spreadsheetsMimeType }'`;
		const url = `${ GoogleApi.DRIVE_BASE_URL }/files?q=${ encodeURIComponent( query ) }`;
		const result = await this.fetchApi< GoogleDriveFileList >( url );

		return result.files ?? [];
	}

	public async getSpreadsheetsOptions(): Promise< SelectOption[] > {
		const spreadsheets = await this.getSpreadsheetList();
		return spreadsheets.map( spreadsheet => ( {
			label: spreadsheet.name,
			value: spreadsheet.id,
		} ) );
	}

	public async getSpreadsheet( spreadsheetId: string ): Promise< GoogleSpreadsheet > {
		const url = `${ GoogleApi.SHEETS_BASE_URL }/spreadsheets/${ spreadsheetId }`;
		const result = await this.fetchApi< GoogleSpreadsheet >( url );
		return result;
	}

	public async getSheetsOptions( spreadsheetId: string ): Promise< SelectOption[] > {
		const spreadsheet = await this.getSpreadsheet( spreadsheetId );
		return spreadsheet.sheets.map( sheet => ( {
			label: sheet.properties.title,
			value: sheet.properties.sheetId.toString(),
		} ) );
	}
}
