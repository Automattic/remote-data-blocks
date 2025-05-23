import {
	Button,
	IconType,
	Placeholder as PlaceholderComponent,
	__experimentalToggleGroupControl as ToggleGroupControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { cloud } from '@wordpress/icons';

// import { ItemSelectQueryType } from './ItemSelectQueryType';

// Inline type for selector from BlockConfig
// type Selector = {
// 	image_url?: string;
// 	inputs: InputVariable[];
// 	name: string;
// 	query_key: string;
// 	display_name?: string;
// 	type: string;
// };

export interface QuerySelectionPlaceholderProps {
	blockConfig: BlockConfig;
	onDisplayQuerySelected: ( displayQuery: string ) => void;
}

export function QuerySelectionPlaceholder( {
	blockConfig,
	onDisplayQuerySelected,
}: QuerySelectionPlaceholderProps ) {
	const { instructions, settings, variations } = blockConfig;

	const iconElement: IconType = ( settings.icon as IconType ) ?? cloud;

	return (
		<PlaceholderComponent
			icon={ iconElement }
			label={ settings.title }
			instructions={ instructions ?? __( 'Pick the query you want to use.' ) }
		>
			<ToggleGroupControl
				className="remote-data-blocks-button-group"
				label={ __( 'Select a display query' ) }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			>
				{ Object.entries( variations ).map( ( [ queryKey, config ] ) => (
					<Button
						key={ queryKey }
						variant="primary"
						onClick={ () => onDisplayQuerySelected( queryKey ) }
					>
						{ config.name }
					</Button>
				) ) }
			</ToggleGroupControl>
		</PlaceholderComponent>
	);
}
