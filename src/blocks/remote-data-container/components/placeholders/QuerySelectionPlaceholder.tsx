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
// 	query_group: string;
// };

export interface QuerySelectionPlaceholderProps {
	blockConfig: BlockConfig;
	onDisplayQuerySelected: ( displayQuery: string ) => void;
}

export function QuerySelectionPlaceholder( {
	blockConfig,
	onDisplayQuerySelected,
}: QuerySelectionPlaceholderProps ) {
	const { instructions, settings, selectors } = blockConfig;

	const iconElement: IconType = ( settings.icon as IconType ) ?? cloud;

	// const [ selectedDisplayQuery, setSelectedDisplayQuery ] = useState< string | null >(  );
	// const [ showSelectors, setShowSelectors ] = useState< boolean >( false );

	// function handleSelectorOnSelect( inputs: RemoteDataQueryInput[] ) {
	// 	setShowSelectors( false );
	// 	onQueryGroupSelect( selectedGroup );
	// 	onQueryInputsSelect( inputs );
	// }

	// function handleGroupOnSelect( group: string ) {
	// 	// setSelectedGroup( group );
	// 	setShowSelectors( true );
	// }

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
				label={ __( 'Select a display query' ) }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			>
				{ selectors.map( selector => (
					<Button
						key={ selector.name }
						variant="primary"
						onClick={ () => {
							onDisplayQuerySelected( selector.query_key );
						} }
					>
						{ selector.display_name }
					</Button>
				) ) }
			</ToggleGroupControl>
			{ /* { showSelectors && (
				<ItemSelectQueryType
					blockName={ blockConfig.name }
					selectors={ selectors }
					onSelect={ handleSelectorOnSelect }
				/>
			) } */ }
		</PlaceholderComponent>
	);
}
