import {
	Button,
	__experimentalToggleGroupControl as ToggleGroupControl,
	IconType,
	Placeholder as PlaceholderComponent,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { cloud } from '@wordpress/icons';
import { ItemSelectQueryType } from './ItemSelectQueryType';

// Inline type for selector from BlockConfig
// (could also import BlockConfig and use BlockConfig['selectors'][0] if preferred)
type Selector = {
	image_url?: string;
	inputs: InputVariable[];
	name: string;
	query_key: string;
	display_name?: string;
	type: string;
};

export interface QuerySelectionPlaceholderProps {
	blockConfig: BlockConfig;
	onSelect: ( queryKey: string ) => void;
}

export function QuerySelectionPlaceholder( props: QuerySelectionPlaceholderProps ) {
	const { blockConfig, onSelect } = props;
	const { instructions, settings, selectors } = blockConfig;

	const iconElement: IconType = ( settings.icon as IconType ) ?? cloud;
	const [ selectedSelector, setSelectedSelector ] = useState< Selector | null >( null );

	if ( selectedSelector ) {
		return (
			<ItemSelectQueryType
				blockName={ blockConfig.name }
				selector={ selectedSelector }
				onSelect={ () => {
					setSelectedSelector( null );
					if ( selectedSelector && typeof selectedSelector.query_key === 'string' ) {
						onSelect( selectedSelector.query_key );
					}
				} }
			/>
		);
	}

	return (
		<PlaceholderComponent
			icon={ iconElement }
			label={ settings.title }
			instructions={
				instructions ?? __( 'This block requires selection of one or more items for display.' )
			}
		>
			<ToggleGroupControl
				className="remote-data-blocks-button-group"
				label={ __( '' ) }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			>
				{ selectors.map( selector => (
					<Button
						key={ selector.query_key }
						variant="primary"
						onClick={ () => setSelectedSelector( selector ) }
					>
						{ selector.display_name ?? selector.name }
					</Button>
				) ) }
			</ToggleGroupControl>
		</PlaceholderComponent>
	);
}
