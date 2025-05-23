import {
	Button,
	Dropdown,
	DropdownMenu,
	MenuItem,
	Path,
	SVG,
	__experimentalToggleGroupControl as ToggleGroupControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { DataViewsModal } from '@/blocks/remote-data-container/components/modals/DataViewsModal';
import { InputModal } from '@/blocks/remote-data-container/components/modals/InputModal';
import { InputPopover } from '@/blocks/remote-data-container/components/popovers/InputPopover';

// Inline type for selector from BlockConfig
type Selector = {
	image_url?: string;
	inputs: InputVariable[];
	name: string;
	query_key: string;
	display_name?: string;
	type: string;
};

interface ItemSelectQueryTypeProps {
	blockName: string;
	displayQueryKey: string;
	variationConfig: BlockConfig[ 'variations' ][ 0 ];
	onSelect: ( data: RemoteDataQueryInput[] ) => void;
}

const InputSourceButton = ( {
	label,
	options,
	onClick,
}: {
	label: string;
	options: { icon: JSX.Element | null; label: string; onClick: () => void }[];
	onClick: () => void;
} ) => {
	return (
		<Dropdown
			renderToggle={ ( { isOpen, onToggle, onClose } ) => (
				<Button variant="primary" onClick={ onToggle }>
					{ label }
				</Button>
			) }
			renderContent={ () => (
				<>
					{ options.map( option => (
						<MenuItem key={ option.label } icon={ option.icon } onClick={ option.onClick }>
							{ option.label }
						</MenuItem>
					) ) }
				</>
			) }
		>
			label={ label }
		</Dropdown>
	);
};

export function ItemSelectQueryType( props: ItemSelectQueryTypeProps ) {
	const { blockName, displayQueryKey, variationConfig, onSelect } = props;

	return (
		<>
			{ Object.entries( variationConfig.inputs ).map( ( [ inputKey, inputConfig ] ) => (
				<InputSourceButton
					key={ inputKey }
					label={ inputConfig.name }
					options={ inputConfig.sources.map( sourceConfig => ( {
						icon: null,
						label: sourceConfig.display_name,
						onClick: () => {},
					} ) ) }
				/>
			) ) }
		</>
	);

	// return (
	// 	<ToggleGroupControl
	// 		className="remote-data-blocks-button-group"
	// 		label={ __( '' ) }
	// 		__nextHasNoMarginBottom
	// 		__next40pxDefaultSize
	// 	>
	// 		{ Object.entries( variationConfig.inputs )?.map( ( [ inputKey, inputConfig ] ) => {
	// 			const title = inputKey;
	// 			const selectorProps = {
	// 				blockName,
	// 				headerImage: inputConfig.image_url,
	// 				inputVariables: inputConfig.inputs,
	// 				onSelect,
	// 				queryKey: inputConfig.query_key,
	// 				title,
	// 			};

	// 			switch ( inputConfig.type ) {
	// 				case 'search':
	// 				case 'list':
	// 					return (
	// 						<DataViewsModal
	// 							className="rdb-editor_dataviews-modal-item-select"
	// 							key={ title }
	// 							{ ...selectorProps }
	// 						/>
	// 					);
	// 				case 'load-without-input':
	// 					return (
	// 						<Button
	// 							key={ title }
	// 							onClick={ () => {
	// 								onSelect( [ {} ] );
	// 							} }
	// 							variant="primary"
	// 						>
	// 							{ selector.name }
	// 						</Button>
	// 					);
	// 				case 'manual-input':
	// 					if ( selector.inputs.length === 1 && selector.inputs[ 0 ] ) {
	// 						return (
	// 							<InputPopover
	// 								key={ title }
	// 								input={ selector.inputs[ 0 ] }
	// 								{ ...selectorProps }
	// 								title={ selector.inputs[ 0 ].name ?? selector.name }
	// 							/>
	// 						);
	// 					}
	// 					return <InputModal key={ title } inputs={ selector.inputs } { ...selectorProps } />;
	// 			}

	// 			return null;
	// 		} ) }
	// 	</ToggleGroupControl>
	// );
}
