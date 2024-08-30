import { BLOCK_BINDING_SOURCE } from '@/config/constants';
import { isObjectWithStringKeys } from '@/utils/type-narrowing';

function getAttributeValue( attributes: unknown, key: string | undefined | null ): string {
	if ( ! key || ! isObjectWithStringKeys( attributes ) ) {
		return '';
	}

	// This .toString() call is important to handle RichTextData objects. We may
	// set the attribute value as a string, but once loaded by the editor, it will
	// be a RichTextData object. Currently, .toString() proxies to .toHTMLString():
	//
	// https://github.com/WordPress/gutenberg/blob/7bca2fadddde7b2b2f62823b8a4b81378f117412/packages/rich-text/src/create.js#L157
	return attributes[ key ]?.toString() ?? '';
}

function getExpectedAttributeValue(
	result?: Record< string, string >,
	args?: RemoteDataBlockBindingArgs
): string | null {
	if ( ! args?.field || ! result?.[ args.field ] ) {
		return null;
	}

	let expectedValue = result[ args.field ];
	if ( args.label ) {
		expectedValue = `${ args.label }: ${ expectedValue }`;
	}

	return expectedValue ?? null;
}

export function getBoundAttributeEntries(
	attributes: RemoteDataInnerBlockAttributes
): [ string, RemoteDataBlockBinding ][] {
	return Object.entries( attributes.metadata?.bindings ?? {} ).filter(
		( [ _target, binding ] ) => binding.source === BLOCK_BINDING_SOURCE
	);
}

export function getMismatchedAttributes(
	attributes: RemoteDataInnerBlockAttributes,
	results: RemoteData[ 'results' ],
	index = 0
): Partial< RemoteDataInnerBlockAttributes > {
	return Object.fromEntries(
		getBoundAttributeEntries( attributes )
			.map( ( [ target, binding ] ) => [
				target,
				getExpectedAttributeValue( results[ index ], binding.args ),
			] )
			.filter(
				( [ target, value ] ) => null !== value && value !== getAttributeValue( attributes, target )
			)
	) as Partial< RemoteDataInnerBlockAttributes >;
}

export function hasRemoteDataChanged( one: RemoteData, two: RemoteData ): boolean {
	if ( ! one || ! two ) {
		return true;
	}

	// Remove result ID and metadata from comparison
	const { metadata: _removed1, resultId: _removed2, ...clean1 } = one;
	const { metadata: _removed3, resultId: _removed4, ...clean2 } = two;

	return JSON.stringify( clean1 ) !== JSON.stringify( clean2 );
}
