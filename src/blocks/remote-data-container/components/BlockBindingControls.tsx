import { Button, CheckboxControl, Popover, SelectControl } from '@wordpress/components';
import { useEffect, useId, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { chevronRightSmall, closeSmall, Icon, plus } from '@wordpress/icons';

import {
	BUTTON_TEXT_FIELD_TYPES,
	BUTTON_URL_FIELD_TYPES,
	HTML_FIELD_TYPES,
	IMAGE_ALT_FIELD_TYPES,
	IMAGE_URL_FIELD_TYPES,
	TEXT_FIELD_TYPES,
} from '@/blocks/remote-data-container/config/constants';
import { sendTracksEvent } from '@/blocks/remote-data-container/utils/tracks';
import { BLOCK_BINDING_SOURCE, PATTERN_OVERRIDES_BINDING_SOURCE } from '@/config/constants';
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
	isSelected?: boolean;
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
		isSelected = true,
		remoteDataName,
		supportedAttributes,
		removeBinding,
		updateBinding,
	} = props;
	const [ chooser, setChooser ] = useState< {
		target: string;
		field: string;
		anchor: HTMLButtonElement;
	} | null >( null );
	const triggers = useRef< Record< string, HTMLButtonElement | null > >( {} );
	const descriptionId = useId();

	useEffect( () => {
		setChooser( null );
	}, [ blockName, remoteDataName, isSelected ] );

	function closeChooser(): void {
		setChooser( null );
		chooser?.anchor.focus();
	}

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
	function getBinding( target: string ) {
		return (
			bindings[ target ] ??
			( supportedAttributes.includes( target ) &&
			bindings.__default?.source === PATTERN_OVERRIDES_BINDING_SOURCE
				? bindings.__default
				: undefined )
		);
	}

	const hasLabelControl =
		[ 'core/paragraph', 'core/heading' ].includes( blockName ) && Boolean( contentField );

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
			<div className="rdb-binding-rows">
				{ targets.map( target => {
					const binding = getBinding( target );
					const isRemoteBinding = binding?.source === BLOCK_BINDING_SOURCE;
					const field = isRemoteBinding ? binding.args.field : '';
					const label = getAttributeLabel( blockName, target );
					const isOtherBinding = binding && ! isRemoteBinding;
					return (
						<div className="rdb-binding-row" key={ target } data-binding-target={ target }>
							<Button
								className="rdb-binding-row__trigger"
								ref={ element => {
									triggers.current[ target ] = element;
								} }
								aria-label={ sprintf(
									isRemoteBinding
										? __( 'Change %s binding', 'remote-data-blocks' )
										: __( 'Bind %s', 'remote-data-blocks' ),
									label
								) }
								aria-expanded={ chooser?.target === target }
								aria-describedby={
									isRemoteBinding || isOtherBinding ? `${ descriptionId }-${ target }` : undefined
								}
								aria-haspopup="dialog"
								onClick={ () => {
									const anchor = triggers.current[ target ];
									if ( anchor ) {
										setChooser( { target, field, anchor } );
									}
								} }
							>
								<span
									className="rdb-binding-row__description"
									id={ `${ descriptionId }-${ target }` }
								>
									<span
										className={
											isRemoteBinding || isOtherBinding ? 'rdb-binding-row__attribute' : undefined
										}
									>
										{ label }
									</span>
									{ isRemoteBinding && (
										<>
											<span>{ availableBindings[ field ]?.name ?? field }</span>
											{ ! availableBindings[ field ] && (
												<span className="rdb-binding-row__status">
													{ __( 'Field unavailable', 'remote-data-blocks' ) }
												</span>
											) }
										</>
									) }
									{ isOtherBinding && (
										<span>{ __( 'Managed by another binding', 'remote-data-blocks' ) }</span>
									) }
								</span>
								<Icon
									className="rdb-binding-row__indicator"
									icon={ isRemoteBinding ? chevronRightSmall : plus }
									size={ 20 }
								/>
							</Button>
							{ isRemoteBinding && (
								<Button
									className="rdb-binding-row__remove"
									icon={ closeSmall }
									size="compact"
									label={ sprintf( __( 'Remove %s binding', 'remote-data-blocks' ), label ) }
									aria-label={ sprintf( __( 'Remove %s binding', 'remote-data-blocks' ), label ) }
									showTooltip
									onClick={ () => {
										updateFieldBinding( target, '' );
										triggers.current[ target ]?.focus();
									} }
								/>
							) }
						</div>
					);
				} ) }
			</div>
			{ chooser &&
				isSelected &&
				( () => {
					const { target, field, anchor } = chooser;
					const binding = getBinding( target );
					const currentField = binding?.source === BLOCK_BINDING_SOURCE ? binding.args.field : '';
					const replacesOtherSource =
						Boolean( binding ) && binding?.source !== BLOCK_BINDING_SOURCE;
					const fieldTypes = supportedAttributes.includes( target )
						? getBindingFieldTypes( blockName, target, getBindingTargetType( blockName, target ) )
						: [];
					const hasChoices =
						Boolean( currentField ) ||
						Object.values( availableBindings ).some( mapping =>
							fieldTypes.includes( mapping.type )
						);
					const label = getAttributeLabel( blockName, target );
					return (
						<Popover
							anchor={ anchor }
							placement="left-start"
							onClose={ closeChooser }
							onFocusOutside={ () => setChooser( null ) }
							focusOnMount="firstElement"
							className="rdb-binding-chooser"
							role="dialog"
							aria-labelledby={ `${ descriptionId }-heading` }
						>
							<h3 className="rdb-binding-chooser__heading" id={ `${ descriptionId }-heading` }>
								{ sprintf(
									currentField
										? __( 'Change %s binding', 'remote-data-blocks' )
										: __( 'Bind %s', 'remote-data-blocks' ),
									label
								) }
							</h3>
							{ replacesOtherSource && hasChoices && (
								<p
									className="rdb-binding-chooser__warning"
									id={ `${ descriptionId }-replace-warning` }
								>
									{ __(
										'This attribute is managed by another binding. Applying a remote field replaces it.',
										'remote-data-blocks'
									) }
								</p>
							) }
							{ hasChoices ? (
								<BlockBindingFieldControl
									availableBindings={ availableBindings }
									fieldTypes={ fieldTypes }
									suggestedFieldTypes={ getSuggestedBindingFieldTypes( blockName, target ) }
									help={
										target === 'url'
											? __( 'Choose a field containing a URL.', 'remote-data-blocks' )
											: undefined
									}
									label={ __( 'Remote field', 'remote-data-blocks' ) }
									target={ target }
									updateFieldBinding={ ( _target, nextField ) =>
										setChooser( { ...chooser, field: nextField } )
									}
									value={ field }
								/>
							) : (
								<p>
									{ __(
										'No matching fields are available for this attribute.',
										'remote-data-blocks'
									) }
								</p>
							) }
							<div className="rdb-binding-chooser__actions">
								<Button variant="tertiary" onClick={ closeChooser }>
									{ hasChoices
										? __( 'Cancel', 'remote-data-blocks' )
										: __( 'Close', 'remote-data-blocks' ) }
								</Button>
								{ hasChoices && (
									<Button
										variant="primary"
										aria-describedby={
											replacesOtherSource ? `${ descriptionId }-replace-warning` : undefined
										}
										disabled={ ! field || field === currentField }
										onClick={ () => {
											updateFieldBinding( target, field );
											closeChooser();
										} }
									>
										{ __( 'Apply', 'remote-data-blocks' ) }
									</Button>
								) }
							</div>
						</Popover>
					);
				} )() }

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
