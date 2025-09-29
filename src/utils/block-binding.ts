import { cloneBlock } from '@wordpress/blocks';

import { BLOCK_BINDING_SOURCE } from '@/config/constants';
import { getRemoteDataResultValue } from '@/utils//remote-data';
import { getClassName } from '@/utils/string';

import type { BlockPattern } from '@wordpress/block-editor';
import type { BlockInstance } from '@wordpress/blocks';

/**
 * Clone a block and inject previewIndex so that it can be previewed in a
 * template loop. Template previews are previewing a specific result from the
 * result set, so a preview index is added to the binding args.
 */
export function cloneBlockForTemplatePreview(
	block: BlockInstance< RemoteDataInnerBlockAttributes >,
	result: RemoteDataApiResult,
	remoteDataBlockName: string,
	previewIndex: number
): BlockInstance {
	const newInnerBlocks = block.innerBlocks?.map( innerBlock =>
		cloneBlockForTemplatePreview( innerBlock, result, remoteDataBlockName, previewIndex )
	);

	const clonedAttributes = {
		...block.attributes,
		metadata: {
			...block.attributes.metadata,
			bindings: {
				...block.attributes.metadata?.bindings,
				...Object.fromEntries(
					getBoundAttributeEntries( block.attributes, remoteDataBlockName ).map(
						( [ target, binding ] ) => [
							target,
							{
								...binding,
								args: {
									...binding.args,
									isPreview: true,
									previewIndex,
								},
							},
						]
					)
				),
			},
		},
	};

	return cloneBlock( block, clonedAttributes, newInnerBlocks );
}

/**
 * Clone a block and inject remote data so that it can be a pattern preview or a template preview. Template previews may be previewing
 * a specific result from the result set, so a preview index can be provided.
 */
export function cloneBlockForPatternPreview(
	block: BlockInstance< RemoteDataInnerBlockAttributes >,
	result: RemoteDataApiResult,
	remoteDataBlockName: string
): BlockInstance {
	const newInnerBlocks = block.innerBlocks?.map( innerBlock =>
		cloneBlockForPatternPreview( innerBlock, result, remoteDataBlockName )
	);

	const clonedAttributes = {
		...block.attributes,
		metadata: {
			...block.attributes.metadata,
			bindings: {
				...block.attributes.metadata?.bindings,
				...Object.fromEntries(
					getBoundAttributeEntries( block.attributes, remoteDataBlockName ).map(
						( [ target, binding ] ) => [
							target,
							{
								...binding,
								args: {
									...binding.args,
									isPreview: true,
									previewValue: getExpectedAttributeValue( result, binding.args ),
								},
							},
						]
					)
				),
			},
		},
	};

	return cloneBlock( block, clonedAttributes, newInnerBlocks );
}

function getExpectedAttributeValue(
	result?: RemoteDataApiResult,
	args?: RemoteDataBlockBindingArgs
): string | undefined {
	if ( ! args?.field || ! result?.result?.[ args.field ] ) {
		return;
	}

	// See comment on toString() in getAttributeValue.
	let expectedValue = getRemoteDataResultValue( result, args.field );
	if ( args.label ) {
		const labelClass = getClassName( 'block-label' );
		expectedValue = `<span class="${ labelClass }">${ args.label }</span> ${ expectedValue }`;
	}

	return expectedValue;
}

export function getBoundAttributeEntries(
	attributes: RemoteDataInnerBlockAttributes,
	remoteDataBlockName: string
): [ string, RemoteDataBlockBinding ][] {
	return Object.entries( attributes.metadata?.bindings ?? {} ).filter(
		( [ _target, binding ] ) =>
			binding.source === BLOCK_BINDING_SOURCE && binding.args?.block === remoteDataBlockName
	);
}

export function getBoundBlockClassName(
	attributes: RemoteDataInnerBlockAttributes,
	remoteDataBlockName: string
): string {
	const existingClassNames = ( attributes.className ?? '' )
		.split( /\s/ )
		.filter( className => ! className.startsWith( 'rdb-block-data-' ) );
	const classNames = new Set< string | undefined >( [
		...existingClassNames,
		...getBoundAttributeEntries( attributes, remoteDataBlockName ).map( ( [ _target, binding ] ) =>
			getClassName( `block-data-${ binding.args.field }` )
		),
	] );

	return Array.from( classNames.values() ).filter( Boolean ).join( ' ' );
}

/**
 * Recursively determine if a block or its inner blocks have any block bindings.
 */
export function hasBlockBinding(
	block: BlockInstance< RemoteDataInnerBlockAttributes >,
	remoteDataBlockName: string
): boolean {
	if ( getBoundAttributeEntries( block.attributes, remoteDataBlockName ).length > 0 ) {
		return true;
	}

	return (
		block.innerBlocks?.some( innerBlock => hasBlockBinding( innerBlock, remoteDataBlockName ) ) ??
		false
	);
}

export function hasRemoteDataChanged( one?: RemoteData, two?: RemoteData ): boolean {
	if ( ! one || ! two ) {
		return true;
	}

	// Remove result ID and metadata properties from comparison. Compare results
	// separately to remove UUID.
	const { metadata: _removed1, resultId: _removed2, results: results1, ...clean1 } = one;
	const { metadata: _removed3, resultId: _removed4, results: results2, ...clean2 } = two;

	const cleanedResults1 = results1.map( ( { uuid: _removed, ...result } ) => result );
	const cleanedResults2 = results2.map( ( { uuid: _removed, ...result } ) => result );

	if ( JSON.stringify( cleanedResults1 ) !== JSON.stringify( cleanedResults2 ) ) {
		return true;
	}

	return JSON.stringify( clean1 ) !== JSON.stringify( clean2 );
}

/**
 * Determine if a block pattern is a synced pattern / resuable block.
 */
export function isSyncedPattern( pattern: BlockPattern ): boolean {
	return Boolean( pattern.id && pattern.syncStatus !== 'unsynced' );
}
