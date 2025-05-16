import {
	Button,
	IconType,
	Placeholder as PlaceholderComponent,
	__experimentalToggleGroupControl as ToggleGroupControl,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { cloud } from '@wordpress/icons';

import { ItemSelectQueryType } from './ItemSelectQueryType';

// Inline type for selector from BlockConfig
type Selector = {
	image_url?: string;
	inputs: InputVariable[];
	name: string;
	query_key: string;
	display_name?: string;
	type: string;
	query_group: string;
};

export interface QuerySelectionPlaceholderProps {
	blockConfig: BlockConfig;
	onQueryGroupSelect: ( group: string ) => void;
	onQueryKeySelect: ( key: string ) => void;
	onQueryInputsSelect: ( inputs: RemoteDataQueryInput[] ) => void;
}

export function QuerySelectionPlaceholder( props: QuerySelectionPlaceholderProps ) {
	const { blockConfig, onQueryGroupSelect, onQueryKeySelect, onQueryInputsSelect } = props;
	const { instructions, settings, selectors } = blockConfig;

	const iconElement: IconType = ( settings.icon as IconType ) ?? cloud;

	// Create a unique list of query groups
	const queryGroups: string[] = [
		...new Set( selectors.map( ( selector: Selector ) => selector.query_group ) ),
	];

	const [ selectedGroup, setSelectedGroup ] = useState< string >( '' );
	const [ showSelectors, setShowSelectors ] = useState< boolean >( false );

	function handleSelectorOnSelect( queryKey: string, inputs: RemoteDataQueryInput[] ) {
		setShowSelectors( false );
		onQueryGroupSelect( selectedGroup );
		onQueryKeySelect( queryKey );
		onQueryInputsSelect( inputs );
	}

	function handleGroupOnSelect( group: string ) {
		setSelectedGroup( group );
		setShowSelectors( true );
	}

	return (
		<PlaceholderComponent
			icon={ iconElement }
			label={ settings.title }
			instructions={
				instructions ?? __( 'This block requires selection of one or more items for display.' )
			}
		>
			{ ! showSelectors && (
				<ToggleGroupControl
					className="remote-data-blocks-button-group"
					label={ __( '' ) }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				>
					{ queryGroups.map( ( group: string ) => (
						<Button
							key={ group }
							variant="primary"
							onClick={ () => {
								handleGroupOnSelect( group );
							} }
						>
							{ group
								.replace( /[^a-zA-Z0-9]/g, ' ' )
								.replace( /\b\w/g, ( initialLetter: string ) => initialLetter.toUpperCase() ) }
						</Button>
					) ) }
				</ToggleGroupControl>
			) }
			{ showSelectors && (
				<ItemSelectQueryType
					blockName={ blockConfig.name }
					selectors={ selectors.filter( selector => selector.query_group === selectedGroup ) }
					onSelect={ handleSelectorOnSelect }
				/>
			) }
		</PlaceholderComponent>
	);
}
