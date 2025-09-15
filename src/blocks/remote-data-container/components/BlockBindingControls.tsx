import { useBlockBindingsUtils } from '@wordpress/block-editor';
import { CheckboxControl, SelectControl } from '@wordpress/components';

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
import { getBlockDataSourceType } from '@/utils/localized-block-data';

interface BlockBindingFieldControlProps {
	availableBindings: AvailableBindings;
	fieldTypes: string[];
	label: string;
	target: string;
	updateFieldBinding: ( target: string, field: string ) => void;
	value?: string;
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
	args?: RemoteDataBlockBindingArgs;
	availableBindings: AvailableBindings;
	blockName: string;
	remoteDataName: string;
}

export function BlockBindingControls( props: BlockBindingControlsProps ) {
	const { args, availableBindings, blockName, remoteDataName } = props;

	const { updateBlockBindings } = useBlockBindingsUtils();

	function removeBinding( target: string ): void {
		updateBlockBindings( {
			[ target ]: undefined,
		} );
	}

	function updateBinding(
		target: string,
		newArgs: Omit< RemoteDataBlockBindingArgs, 'block' >
	): void {
		updateBlockBindings( {
			[ target ]: {
				source: BLOCK_BINDING_SOURCE,
				args: {
					...newArgs,
					block: remoteDataName,
				},
			},
		} );
	}

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

		updateBinding( target, { ...args, field } );
		sendTracksEvent( 'remote_data_container_actions', {
			action: 'update_binding',
			data_source_type: getBlockDataSourceType( remoteDataName ),
			remote_data_field: field,
			block_target_attribute: target,
		} );
	}

	function updateFieldLabel( showLabel: boolean ): void {
		if ( ! args?.field ) {
			return;
		}

		const label = showLabel
			? Object.entries( availableBindings ).find( ( [ key ] ) => key === args?.field )?.[ 1 ]?.name
			: undefined;
		updateBinding( 'content', { ...args, label } );
		sendTracksEvent( 'remote_data_container_actions', {
			action: showLabel ? 'show_label' : 'hide_label',
			data_source_type: getBlockDataSourceType( remoteDataName ),
		} );
	}

	switch ( blockName ) {
		case 'core/heading':
		case 'core/paragraph':
			return (
				<>
					<BlockBindingFieldControl
						availableBindings={ availableBindings }
						fieldTypes={ TEXT_FIELD_TYPES }
						label="Content"
						target="content"
						updateFieldBinding={ updateFieldBinding }
						value={ args?.field }
					/>
					<CheckboxControl
						checked={ Boolean( args?.label ) }
						disabled={ ! args?.field }
						label="Show label"
						name="show_label"
						onChange={ updateFieldLabel }
					/>
				</>
			);

		case 'core/image':
			return (
				<>
					<BlockBindingFieldControl
						availableBindings={ availableBindings }
						fieldTypes={ IMAGE_URL_FIELD_TYPES }
						label="Image URL"
						target="url"
						updateFieldBinding={ updateFieldBinding }
						value={ args?.field }
					/>
					<BlockBindingFieldControl
						availableBindings={ availableBindings }
						fieldTypes={ IMAGE_ALT_FIELD_TYPES }
						label="Image alt text"
						target="alt"
						updateFieldBinding={ updateFieldBinding }
						value={ args?.field }
					/>
				</>
			);
		case 'core/button':
			return (
				<>
					<BlockBindingFieldControl
						availableBindings={ availableBindings }
						fieldTypes={ BUTTON_URL_FIELD_TYPES }
						label="Button URL"
						target="url"
						updateFieldBinding={ updateFieldBinding }
						value={ args?.field }
					/>
					<BlockBindingFieldControl
						availableBindings={ availableBindings }
						fieldTypes={ BUTTON_TEXT_FIELD_TYPES }
						label="Button Text"
						target="text"
						updateFieldBinding={ updateFieldBinding }
						value={ args?.field }
					/>
				</>
			);

		case 'remote-data-blocks/remote-html':
			return (
				<>
					<BlockBindingFieldControl
						availableBindings={ availableBindings }
						fieldTypes={ HTML_FIELD_TYPES }
						label="Raw HTML"
						target="content"
						updateFieldBinding={ updateFieldBinding }
						value={ args?.field }
					/>
				</>
			);
	}

	return null;
}
