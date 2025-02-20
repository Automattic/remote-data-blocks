import { SelectOption } from '@/types/input';
import { WebStoreRecord, WebStoreResponse } from '@/types/salesforceD2C';

export class SalesforceD2CApi {
	constructor( private token: string | null, private domain: string | null ) {}

	private static WEB_STORES_LIST_SUFFIX_URL = `.my.salesforce.com/services/data/v63.0/query/?q=SELECT name,id from webstore`;

	private getAuthHeaders() {
		if ( ! this.token ) {
			throw new Error( 'No token provided' );
		}

		if ( ! this.domain ) {
			throw new Error( 'No domain provided' );
		}

		return {
			Authorization: `Bearer ${ this.token }`,
		};
	}

	private async get< T >( url: string, options: RequestInit = {} ): Promise< T > {
		const response = await fetch( url, {
			...options,
			headers: { ...( options.headers ?? {} ), ...this.getAuthHeaders() },
		} );

		if ( ! response.ok ) {
			const errorText = `${ response.status } - ${ await response.text() }`;
			throw new Error( `[Salesforce D2C API] ${ errorText }` );
		}

		return response.json() as Promise< T >;
	}

	private async getWebStoresList(): Promise< WebStoreRecord[] > {
		const url = `https://${ this.domain }${ SalesforceD2CApi.WEB_STORES_LIST_SUFFIX_URL }`;
		const result = await this.get< WebStoreResponse >( url );

		return result?.records ?? [];
	}

	public async getWebStoresOptions(): Promise< SelectOption[] > {
		const webStores = await this.getWebStoresList();
		return webStores.map( webStore => ( {
			label: webStore.Name,
			value: webStore.Id,
		} ) );
	}
}
