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

export interface QuerySelectionPlaceholderProps {
	blockConfig: BlockConfig;
	onQueryGroupSelect: ( group: string ) => void;
	onQueryInputsSelect: ( inputs: RemoteDataQueryInput[] ) => void;
}

export function QuerySelectionPlaceholder( props: QuerySelectionPlaceholderProps ) {
	const { blockConfig, onQueryGroupSelect, onQueryInputsSelect } = props;
	const { instructions, settings, selectors, displayQueriesToSelectors } = blockConfig;

	const iconElement: IconType = ( settings.icon as IconType ) ?? cloud;

	// The keys represent the display query keys.
	const queryGroups: string[] = Object.keys( displayQueriesToSelectors );

	const [ selectedGroup, setSelectedGroup ] = useState< string >( '' );
	const [ showSelectors, setShowSelectors ] = useState< boolean >( false );

	function handleSelectorOnSelect( inputs: RemoteDataQueryInput[] ) {
		setShowSelectors( false );
		onQueryGroupSelect( selectedGroup );
		onQueryInputsSelect( inputs );
	}

	function handleGroupOnSelect( group: string ) {
		setSelectedGroup( group );
		setShowSelectors( true );
	}

	function getSelectorsForGroup( group: string ): Selector[] {
		const selectorKeys = displayQueriesToSelectors[ group ] ?? [];
		return selectors.filter( selector => selectorKeys?.includes( selector.query_key ) );
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
					selectors={ getSelectorsForGroup( selectedGroup ) }
					onSelect={ handleSelectorOnSelect }
				/>
			) }
		</PlaceholderComponent>
	);
}
