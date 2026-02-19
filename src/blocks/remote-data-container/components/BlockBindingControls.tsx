import { store as blockEditorStore } from '@wordpress/block-editor';
import { CheckboxControl, SelectControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { Fragment, useMemo } from '@wordpress/element';

import {
	BUTTON_LINK_TARGET_FIELD_TYPES,
	BUTTON_REL_FIELD_TYPES,
	BUTTON_TEXT_FIELD_TYPES,
	BUTTON_URL_FIELD_TYPES,
	HTML_FIELD_TYPES,
	IMAGE_ALT_FIELD_TYPES,
	IMAGE_TITLE_FIELD_TYPES,
	IMAGE_URL_FIELD_TYPES,
	TEXT_FIELD_TYPES,
} from '@/blocks/remote-data-container/config/constants';
import { sendTracksEvent } from '@/blocks/remote-data-container/utils/tracks';
import { getBlockDataSourceType } from '@/utils/localized-block-data';

interface BlockBindingFieldControlProps {
	availableBindings: AvailableBindings;
	fieldTypes: string[];
	label: string;
	target: string;
	updateFieldBinding: ( target: string, field: string ) => void;
	value: string;
}

export function BlockBindingFieldControl( props: BlockBindingFieldControlProps ) {
	const { availableBindings, fieldTypes, label, target, updateFieldBinding, value } = props;

	const options = Object.entries( availableBindings )
		.filter( ( [ _key, mapping ] ) => fieldTypes.includes( mapping.type ) )
		.map( ( [ key, mapping ] ) => {
			return { label: mapping.name, value: key };
		} );

	return (
		<SelectControl
			label={ label }
			name={ target }
			options={ [ { label: 'Select a field', value: '' }, ...options ] }
			onChange={ ( field: string ) => updateFieldBinding( target, field ) }
			value={ value }
			__next40pxDefaultSize
			__nextHasNoMarginBottom
		/>
	);
}

interface BlockBindingControlsProps {
	attributes: RemoteDataInnerBlockAttributes;
	availableBindings: AvailableBindings;
	blockName: string;
	remoteDataName: string;
	removeBinding: ( target: string ) => void;
	updateBinding: ( target: string, args: Omit< RemoteDataBlockBindingArgs, 'block' > ) => void;
}

/**
 * Get appropriate field types for a given attribute name on a specific block.
 * This provides sensible defaults based on common attribute names and block-specific overrides.
 *
 * @param blockName The name of the block
 * @param attributeName The name of the attribute
 * @returns Array of field types that should be available for this attribute
 */
function getFieldTypesForAttribute( blockName: string, attributeName: string ): string[] {
	// Block-specific attribute mappings
	if ( blockName === 'remote-data-blocks/remote-html' && attributeName === 'content' ) {
		return HTML_FIELD_TYPES;
	}
	
	// Map attribute names to their appropriate field types
	const attributeFieldTypeMap: Record< string, string[] > = {
		// Text content attributes
		content: TEXT_FIELD_TYPES,
		
		// Image attributes
		url: IMAGE_URL_FIELD_TYPES,
		alt: IMAGE_ALT_FIELD_TYPES,
		title: IMAGE_TITLE_FIELD_TYPES,
		
		// Button attributes
		text: BUTTON_TEXT_FIELD_TYPES,
		linkTarget: BUTTON_LINK_TARGET_FIELD_TYPES,
		rel: BUTTON_REL_FIELD_TYPES,
		
		// HTML content
		html: HTML_FIELD_TYPES,
	};

	// Return mapped types or default to TEXT_FIELD_TYPES for unknown attributes
	return attributeFieldTypeMap[ attributeName ] ?? TEXT_FIELD_TYPES;
}

/**
 * Get a human-readable label for an attribute name.
 *
 * @param attributeName The name of the attribute
 * @returns A human-readable label
 */
function getAttributeLabel( attributeName: string ): string {
	const labelMap: Record< string, string > = {
		content: 'Content',
		url: 'URL',
		alt: 'Alt Text',
		title: 'Title',
		text: 'Text',
		linkTarget: 'Link Target',
		rel: 'Link Relationship',
		id: 'ID',
		caption: 'Caption',
	};

	// Return mapped label or capitalize the attribute name
	return labelMap[ attributeName ] ?? attributeName.charAt( 0 ).toUpperCase() + attributeName.slice( 1 );
}

export function BlockBindingControls( props: BlockBindingControlsProps ) {
	const { attributes, availableBindings, blockName, remoteDataName, removeBinding, updateBinding } =
		props;

	// Use useSelect to efficiently get supported bindings from WordPress
	const supportedBindingsFromWP = useSelect(
		( select ) => {
			try {
				const editorSettings = select( blockEditorStore )?.getSettings?.();
				// @ts-expect-error - __experimentalBlockBindingsSupportedAttributes is not in types yet
				return editorSettings?.__experimentalBlockBindingsSupportedAttributes ?? {};
			} catch ( error ) {
				return {};
			}
		},
		[]
	);

	// Memoize the supported attributes for this block
	const supportedAttributes = useMemo( () => {
		// Check if this block has registered bindings in WordPress
		if ( supportedBindingsFromWP[ blockName ] ) {
			return supportedBindingsFromWP[ blockName ];
		}
		
		// Fallback for custom blocks like remote-data-blocks/remote-html
		// that may not register with WordPress but still support bindings
		if ( blockName === 'remote-data-blocks/remote-html' ) {
			return [ 'content' ];
		}
		
		return [];
	}, [ blockName, supportedBindingsFromWP ] );

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

		const args = attributes.metadata?.bindings?.[ target ]?.args ?? {};
		updateBinding( target, { ...args, field } );
		sendTracksEvent( 'remote_data_container_actions', {
			action: 'update_binding',
			data_source_type: getBlockDataSourceType( remoteDataName ),
			remote_data_field: field,
			block_target_attribute: target,
		} );
	}

	function updateFieldLabel( target: string, showLabel: boolean ): void {
		const currentField = attributes.metadata?.bindings?.[ target ]?.args?.field;
		
		if ( ! currentField ) {
			return;
		}

		const label = showLabel
			? Object.entries( availableBindings ).find( ( [ key ] ) => key === currentField )?.[ 1 ]?.name
			: undefined;
		
		const currentArgs = attributes.metadata?.bindings?.[ target ]?.args ?? {};
		updateBinding( target, { ...currentArgs, field: currentField, label } );
		sendTracksEvent( 'remote_data_container_actions', {
			action: showLabel ? 'show_label' : 'hide_label',
			data_source_type: getBlockDataSourceType( remoteDataName ),
		} );
	}

	// If no attributes support bindings, don't render anything
	if ( supportedAttributes.length === 0 ) {
		return null;
	}

	// Dynamically generate controls for each supported attribute
	return (
		<>
			{ supportedAttributes.map( ( attributeName ) => {
				const fieldValue = attributes.metadata?.bindings?.[ attributeName ]?.args?.field ?? '';
				const fieldTypes = getFieldTypesForAttribute( blockName, attributeName );
				const label = getAttributeLabel( attributeName );
				const args = attributes.metadata?.bindings?.[ attributeName ]?.args;

				return (
					<Fragment key={ attributeName }>
						<BlockBindingFieldControl
							availableBindings={ availableBindings }
							fieldTypes={ fieldTypes }
							label={ label }
							target={ attributeName }
							updateFieldBinding={ updateFieldBinding }
							value={ fieldValue }
						/>
						{ attributeName === 'content' && fieldValue && (
							<CheckboxControl
								checked={ Boolean( args?.label ) }
								disabled={ ! fieldValue }
								label="Show label"
								name={ `show_label_${ attributeName }` }
								onChange={ ( showLabel ) => updateFieldLabel( attributeName, showLabel ) }
							/>
						) }
					</Fragment>
				);
			} ) }
		</>
	);
}
