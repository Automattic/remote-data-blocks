function createRemoteDataResult( fields: object, uuid: string ): RemoteDataApiResult {
	return {
		result: Object.fromEntries(
			Object.entries( fields ).map( ( [ key, value ] ) => [
				key,
				{
					name: key,
					type: 'string',
					value,
				},
			] )
		),
		uuid,
	};
}

function isResultMigrated( result: object, uuid: unknown ): boolean {
	return (
		( typeof uuid === 'string' || typeof uuid === 'number' ) &&
		Object.values( result ).every(
			( field: unknown ) =>
				typeof field === 'object' &&
				field !== null &&
				'name' in field &&
				typeof field.name === 'string' &&
				'type' in field &&
				typeof field.type === 'string' &&
				Object.prototype.hasOwnProperty.call( field, 'value' )
		)
	);
}

export function createRemoteDataResults(
	objs: Record< string, unknown >[]
): RemoteDataApiResult[] {
	return objs.map( ( obj, num ) => createRemoteDataResult( obj, `${ num }` ) );
}

/**
 * Create an array of query inputs from an array of remote data results. This is
 * useful when the user has selected items and you want to create the corresponding
 * query inputs.
 */
export function createQueryInputsFromRemoteDataResults(
	results: RemoteDataApiResult[]
): RemoteDataQueryInput[] {
	return results.map( result =>
		Object.fromEntries(
			Object.entries( result.result ).map( ( [ key, value ] ) => [ key, value.value ] )
		)
	);
}

/**
 * Get a remote data result string value by its field name.
 */
export function getRemoteDataResultValue(
	result: RemoteDataApiResult | undefined,
	field: string,
	defaultValue = ''
): string {
	// eslint-disable-next-line security/detect-object-injection
	return result?.result[ field ]?.value?.toString() ?? defaultValue;
}

/**
 * Get the string value of the first result matching a specific type.
 */
export function getFirstRemoteDataResultValueByType(
	result: RemoteDataApiResult,
	type: string,
	defaultValue = ''
): string {
	return (
		Object.values( result.result )
			.find( field => field.type === type )
			?.value?.toString() ?? defaultValue
	);
}

/**
 * Migrate remote data results from the transformed flat object to the format
 * returned by the API.
 */
export function migrateRemoteData( remoteData?: RemoteData ): RemoteData | undefined {
	if ( ! remoteData ) {
		return;
	}

	const { queryInput, results, ...rest } = remoteData;

	const migratedResults = results?.map( ( row, num ) => {
		const { result, uuid } = row;
		const resultMap = result !== null && typeof result === 'object' ? result : undefined;
		if ( resultMap && isResultMigrated( resultMap, uuid ) ) {
			return row;
		}

		// The legacy primitive wrapper has no siblings alongside its result map.
		const fields = resultMap && Object.keys( row ).length === 1 ? resultMap : row;

		return createRemoteDataResult( fields, `${ num }` );
	} );

	return {
		...rest,
		queryInputs: remoteData.queryInputs ?? ( queryInput ? [ queryInput ] : [ {} ] ),
		results: migratedResults,
	};
}
