import {
	BindingValue,
	coerceBindingValue,
	getBindingTargetType,
	isTextBindingTarget,
} from '@/utils/block-binding-value';
import { sanitizeBindingHTML, sanitizeBindingValue } from '@/utils/sanitize-binding-value';

interface RemoteBindingValueOptions {
	args: RemoteDataBlockBindingArgs;
	blockName: string | undefined;
	target: string;
	remoteData: RemoteData | undefined;
	previewIndex: number | undefined;
	getSavedValue: () => unknown;
}

export function resolveRemoteBindingValue( {
	args,
	blockName,
	target,
	remoteData,
	previewIndex,
	getSavedValue,
}: RemoteBindingValueOptions ): BindingValue | undefined {
	if ( ! args.isPreview && ! remoteData?.results?.length ) {
		return undefined;
	}

	const index = args.previewIndex ?? previewIndex ?? 0;
	const rawValue =
		args.previewValue !== undefined
			? args.previewValue
			: remoteData?.results?.[ index ]?.result?.[ args.field ]?.value;
	const targetType = getBindingTargetType( blockName, target );
	const sanitize = ( value: unknown ) =>
		sanitizeBindingValue( coerceBindingValue( value, targetType ), blockName, target );

	// Match Remote HTML's PHP render fallback when the selected field has no value.
	const missingRemoteHTML =
		blockName === 'remote-data-blocks/remote-html' &&
		target === 'content' &&
		( rawValue === null || rawValue === undefined );
	let value = missingRemoteHTML ? null : sanitize( rawValue );
	if ( value === null && ( missingRemoteHTML || ! args.isPreview ) ) {
		value = sanitize( getSavedValue() );
	}
	// Text targets keep their legacy contract of always receiving a string.
	if ( value === null && isTextBindingTarget( targetType ) ) {
		value = '';
	}
	if ( typeof value === 'string' && args.label ) {
		return `${ sanitizeBindingHTML( args.label ) }: ${ value }`;
	}
	return value;
}
