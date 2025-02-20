/** Metadata attributes for a Salesforce record */
export interface SalesforceAttributes {
	/** The type of Salesforce object */
	type: string;
	/** The API URL for this record */
	url: string;
}

/** Represents a single WebStore record */
export interface WebStoreRecord {
	/** Metadata about the WebStore record */
	attributes: SalesforceAttributes;
	/** The name of the WebStore */
	Name: string;
	/** The unique identifier for the WebStore */
	Id: string;
}

/** Response from the Salesforce WebStore API */
export interface WebStoreResponse {
	/** Total number of records in the response */
	totalSize: number;
	/** Whether the query has been completed */
	done: boolean;
	/** Array of WebStore records */
	records: WebStoreRecord[];
}
