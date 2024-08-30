import { SelectControl } from '@wordpress/components';

import {
	IMAGE_FIELD_TYPES,
	TEXT_FIELD_TYPES,
} from '@/blocks/remote-data-container/config/constants';

interface BlockBindingControlsProps {
	attributes: ContextInnerBlockAttributes;
	availableBindings: AvailableBindings;
	blockName: string;
	updateBinding: ( target: string, field?: string ) => void;
}

export function BlockBindingControls( props: BlockBindingControlsProps ) {
	const { attributes, availableBindings, blockName, updateBinding } = props;

	const imageContextOptions = Object.entries( availableBindings )
		.filter( ( [ _key, mapping ] ) => IMAGE_FIELD_TYPES.includes( mapping.type ) )
		.map( ( [ key, mapping ] ) => {
			return { label: mapping.name, value: key };
		} );

	const textContextOptions = Object.entries( availableBindings )
		.filter( ( [ _key, mapping ] ) => TEXT_FIELD_TYPES.includes( mapping.type ) )
		.map( ( [ key, mapping ] ) => {
			return { label: mapping.name, value: key };
		} );

	switch ( blockName ) {
		case 'core/heading':
		case 'core/paragraph':
			return (
				<SelectControl
					label="Content"
					name="content"
					options={ [ { label: 'Select a field', value: '' }, ...textContextOptions ] }
					onChange={ updateBinding.bind( null, 'content' ) }
					value={ attributes.metadata?.bindings?.content?.args?.field }
				/>
			);

		case 'core/image':
			return (
				<>
					<SelectControl
						label="Image URL"
						name="image_url"
						options={ [ { label: 'Select a field', value: '' }, ...imageContextOptions ] }
						onChange={ updateBinding.bind( null, 'url' ) }
						value={ attributes.metadata?.bindings?.url?.args?.field }
					/>
					<SelectControl
						label="Image alt text"
						name="image_alt"
						options={ [ { label: 'Select a field', value: '' }, ...imageContextOptions ] }
						onChange={ updateBinding.bind( null, 'alt' ) }
						value={ attributes.metadata?.bindings?.alt?.args?.field }
					/>
				</>
			);
	}

	return null;
}
