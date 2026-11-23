import { CheckboxControl, SelectControl } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

import {
	BUTTON_TEXT_FIELD_TYPES,
	BUTTON_URL_FIELD_TYPES,
	HTML_FIELD_TYPES,
	IMAGE_ALT_FIELD_TYPES,
	IMAGE_URL_FIELD_TYPES,
	TEXT_FIELD_TYPES,
} from '@/blocks/remote-data-container/config/constants';
import { sendTracksEvent } from '@/blocks/remote-data-container/utils/tracks';
import { BLOCK_BINDING_SOURCE } from '@/config/constants';
import { getBindingTargetType } from '@/utils/block-binding-value';
import { getBlockDataSourceType } from '@/utils/localized-block-data';

/** Schema compatibility determines eligibility; semantic types are suggestions only. */
export function getBindingFieldTypes(
	blockName: string,
	target: string,
	targetType?: string
): string[] {
	// Markup belongs to Remote HTML's isolated renderer, never generic text attributes.
	if ( blockName === 'remote-data-blocks/remote-html' && target === 'content' ) {
		return HTML_FIELD_TYPES;
	}
	if ( targetType === 'number' || targetType === 'integer' ) {
		return targetType === 'integer' ? [ 'integer' ] : [ 'number', 'integer' ];
	}
	if ( targetType === 'boolean' ) {
		return [ 'boolean' ];
	}
	if ( targetType === 'string' || targetType === 'rich-text' ) {
		return [
			...TEXT_FIELD_TYPES,
			'button_text',
			'button_url',
			'image_alt',
			'image_url',
			'id',
			'url',
			'uuid',
		];
	}
	return [];
}

export function getSuggestedBindingFieldTypes( blockName: string, target: string ): string[] {
	const suggestions: Record< string, Record< string, string[] > > = {
		'core/paragraph': { content: TEXT_FIELD_TYPES },
		'core/heading': { content: TEXT_FIELD_TYPES },
		'core/button': { url: BUTTON_URL_FIELD_TYPES, text: BUTTON_TEXT_FIELD_TYPES },
		'core/image': { url: IMAGE_URL_FIELD_TYPES, alt: IMAGE_ALT_FIELD_TYPES },
	};
	return suggestions[ blockName ]?.[ target ] ?? [];
}

function getAttributeLabel( blockName: string, target: string ): string {
	if ( blockName === 'core/image' && target === 'url' ) {
		return __( 'Image URL', 'remote-data-blocks' );
	}
	if ( blockName === 'core/image' && target === 'alt' ) {
		return __( 'Image alt text', 'remote-data-blocks' );
	}
	if ( blockName === 'core/button' && target === 'url' ) {
		return __( 'Button URL', 'remote-data-blocks' );
	}
	if ( blockName === 'core/button' && target === 'text' ) {
		return __( 'Button Text', 'remote-data-blocks' );
	}
	if ( blockName === 'remote-data-blocks/remote-html' && target === 'content' ) {
		return __( 'Raw HTML', 'remote-data-blocks' );
	}
	const labels: Record< string, string > = {
		content: __( 'Content', 'remote-data-blocks' ),
		title: __( 'Title', 'remote-data-blocks' ),
		id: __( 'ID', 'remote-data-blocks' ),
		caption: __( 'Caption', 'remote-data-blocks' ),
		linkTarget: __( 'Link target', 'remote-data-blocks' ),
		rel: __( 'Link relationship', 'remote-data-blocks' ),
		datetime: __( 'Date and time', 'remote-data-blocks' ),
	};
	const humanized = target.replace( /([a-z])([A-Z])/g, '$1 $2' ).replace( /[_-]/g, ' ' );
	return labels[ target ] ?? humanized.charAt( 0 ).toUpperCase() + humanized.slice( 1 );
}

interface BlockBindingFieldControlProps {
	availableBindings: AvailableBindings;
	fieldTypes: string[];
	suggestedFieldTypes?: string[];
	help?: string;
	label: string;
	target: string;
	updateFieldBinding: ( target: string, field: string ) => void;
	value: string;
}

export function BlockBindingFieldControl( props: BlockBindingFieldControlProps ) {
	const {
		availableBindings,
		fieldTypes,
		suggestedFieldTypes = [],
		help,
		label,
		target,
		updateFieldBinding,
		value,
	} = props;
	const compatible = Object.entries( availableBindings ).filter( ( [ _key, mapping ] ) =>
		fieldTypes.includes( mapping.type )
	);
	const suggested = compatible.filter( ( [ _key, mapping ] ) =>
		suggestedFieldTypes.includes( mapping.type )
	);
	const other = compatible.filter(
		( [ _key, mapping ] ) => ! suggestedFieldTypes.includes( mapping.type )
	);
	const hasCurrentOnly = value && ! compatible.some( ( [ key ] ) => key === value );
	const renderOptions = ( fields: typeof compatible ) =>
		fields.map( ( [ key, mapping ] ) => (
			<option key={ key } value={ key }>
				{ mapping.name }
			</option>
		) );
	return (
		<SelectControl
			label={ label }
			name={ target }
			help={ help }
			onChange={ ( field: string ) => updateFieldBinding( target, field ) }
			value={ value }
			__next40pxDefaultSize
			__nextHasNoMarginBottom
		>
			<option value="">{ __( 'Select a field', 'remote-data-blocks' ) }</option>
			{ suggested.length > 0 && (
				<optgroup label={ __( 'Suggested fields', 'remote-data-blocks' ) }>
					{ renderOptions( suggested ) }
				</optgroup>
			) }
			{ other.length > 0 && (
				<optgroup
					label={
						suggested.length
							? __( 'Other compatible fields', 'remote-data-blocks' )
							: __( 'Compatible fields', 'remote-data-blocks' )
					}
				>
					{ renderOptions( other ) }
				</optgroup>
			) }
			{ hasCurrentOnly && (
				<optgroup label={ __( 'Current binding', 'remote-data-blocks' ) }>
					<option value={ value }>
						{ availableBindings[ value ]?.name ??
							sprintf( __( '%s (unavailable)', 'remote-data-blocks' ), value ) }
					</option>
				</optgroup>
			) }
		</SelectControl>
	);
}

interface BlockBindingControlsProps {
	attributes: RemoteDataInnerBlockAttributes;
	availableBindings: AvailableBindings;
	blockName: string;
	remoteDataName: string;
	supportedAttributes: string[];
	removeBinding: ( target: string ) => void;
	updateBinding: ( target: string, args: Omit< RemoteDataBlockBindingArgs, 'block' > ) => void;
}

export function BlockBindingControls( props: BlockBindingControlsProps ) {
	const {
		attributes,
		availableBindings,
		blockName,
		remoteDataName,
		supportedAttributes,
		removeBinding,
		updateBinding,
	} = props;
	const bindings = attributes.metadata?.bindings ?? {};
	const contentArgs =
		bindings.content?.source === BLOCK_BINDING_SOURCE ? bindings.content.args : undefined;
	const contentField = contentArgs?.field ?? '';
	const targets = [
		...new Set( [
			...supportedAttributes,
			...Object.keys( bindings ).filter(
				target => bindings[ target ]?.source === BLOCK_BINDING_SOURCE
			),
		] ),
	];
	const hasLabelControl =
		[ 'core/paragraph', 'core/heading' ].includes( blockName ) && targets.includes( 'content' );

	function updateFieldBinding( target: string, field: string ): void {
		if ( ! field ) {
			removeBinding( target );
			sendTracksEvent( 'remote_data_container_actions', {
				action: 'remove_binding',
				data_source_type: getBlockDataSourceType( remoteDataName ),
				block_target_attribute: target,
			} );
			return;
		}
		const args =
			bindings[ target ]?.source === BLOCK_BINDING_SOURCE ? bindings[ target ]?.args : {};
		updateBinding( target, { ...args, field } );
		sendTracksEvent( 'remote_data_container_actions', {
			action: 'update_binding',
			data_source_type: getBlockDataSourceType( remoteDataName ),
			remote_data_field: field,
			block_target_attribute: target,
		} );
	}

	function updateFieldLabel( showLabel: boolean ): void {
		if ( ! contentField ) {
			return;
		}
		const label = showLabel ? availableBindings[ contentField ]?.name : undefined;
		updateBinding( 'content', { ...contentArgs, field: contentField, label } );
		sendTracksEvent( 'remote_data_container_actions', {
			action: showLabel ? 'show_label' : 'hide_label',
			data_source_type: getBlockDataSourceType( remoteDataName ),
		} );
	}

	return (
		<>
			{ targets.map( target => {
				const binding = bindings[ target ];
				const fieldTypes = supportedAttributes.includes( target )
					? getBindingFieldTypes( blockName, target, getBindingTargetType( blockName, target ) )
					: [];
				return (
					<BlockBindingFieldControl
						key={ target }
						availableBindings={ availableBindings }
						fieldTypes={ fieldTypes }
						suggestedFieldTypes={ getSuggestedBindingFieldTypes( blockName, target ) }
						help={
							target === 'url'
								? __( 'Choose a field containing a URL.', 'remote-data-blocks' )
								: undefined
						}
						label={ getAttributeLabel( blockName, target ) }
						target={ target }
						updateFieldBinding={ updateFieldBinding }
						value={ binding?.source === BLOCK_BINDING_SOURCE ? binding.args.field : '' }
					/>
				);
			} ) }
			{ hasLabelControl && (
				<CheckboxControl
					checked={ Boolean( contentArgs?.label ) }
					disabled={ ! contentField }
					label={ __( 'Show label', 'remote-data-blocks' ) }
					name="show_label"
					onChange={ updateFieldLabel }
				/>
			) }
		</>
	);
}
