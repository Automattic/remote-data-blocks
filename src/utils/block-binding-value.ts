import { getBlockType } from '@wordpress/blocks';

export function isTextBindingTarget( targetType?: string ): boolean {
	return ! targetType || targetType === 'string' || targetType === 'rich-text';
}

export type BindingValue = string | number | boolean | null;

export function getBindingTargetType(
	blockName: string | undefined,
	target: string
): string | undefined {
	const type = blockName ? getBlockType( blockName )?.attributes?.[ target ]?.type : undefined;
	if ( Array.isArray( type ) ) {
		return 'unsupported';
	}
	return typeof type === 'string' ? type : undefined;
}

/** Keep legacy string formatting, converting only explicitly scalar targets. */
export function coerceBindingValue( value: unknown, targetType?: string ): BindingValue {
	if ( targetType === 'number' || targetType === 'integer' ) {
		if ( typeof value !== 'number' && typeof value !== 'string' ) {
			return null;
		}
		if (
			typeof value === 'string' &&
			! /^[+-]?(?:\d+\.?\d*|\.\d+)(?:e[+-]?\d+)?$/i.test( value.trim() )
		) {
			return null;
		}
		const numeric = Number( value );
		return Number.isFinite( numeric ) &&
			( targetType !== 'integer' || Number.isSafeInteger( numeric ) )
			? numeric
			: null;
	}
	if ( targetType === 'boolean' ) {
		return typeof value === 'boolean' ? value : null;
	}
	if ( targetType && ! [ 'string', 'rich-text' ].includes( targetType ) ) {
		return null;
	}
	// Match the PHP resolver: structured values are unresolved (so the saved
	// fallback is used), never serialized as "[object Object]".
	if ( typeof value === 'object' && value !== null ) {
		return null;
	}
	return String( value ?? '' );
}
