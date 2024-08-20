import { SelectControl } from '@wordpress/components';

interface ContextControlsProps {
	attributes: ContextInnerBlockAttributes;
	blockName: string;
	remoteData: RemoteData;
	updateBinding: ( target: string, field?: string ) => void;
}

export function ContextControls( props: ContextControlsProps ) {
	const { attributes, blockName, remoteData, updateBinding } = props;

	const contextOptions = Object.entries( remoteData.availableBindings ).map(
		( [ key, mapping ] ) => {
			return { label: mapping.name, value: key };
		}
	);

	switch ( blockName ) {
		case 'core/heading':
		case 'core/paragraph':
			return (
				<SelectControl
					label="Content"
					name="content"
					options={ [ { label: 'Select a field', value: '' }, ...contextOptions ] }
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
						options={ [ { label: 'Select a field', value: '' }, ...contextOptions ] }
						onChange={ updateBinding.bind( null, 'url' ) }
						value={ attributes.metadata?.bindings?.url?.args?.field }
					/>
					<SelectControl
						label="Image alt text"
						name="image_alt"
						options={ [ { label: 'Select a field', value: '' }, ...contextOptions ] }
						onChange={ updateBinding.bind( null, 'alt' ) }
						value={ attributes.metadata?.bindings?.alt?.args?.field }
					/>
				</>
			);
	}

	return null;
}
