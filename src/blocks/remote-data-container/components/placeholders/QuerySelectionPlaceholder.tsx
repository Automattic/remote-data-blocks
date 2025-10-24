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
import { getSelectorsForDisplayQuery } from '@/utils/localized-block-data';

export interface QuerySelectionPlaceholderProps {
	blockConfig: BlockConfig;
	onDisplayQueryKeySelect: ( displayQueryKey: string ) => void;
	onQueryInputsSelect: ( inputs: RemoteDataQueryInput[] ) => void;
}

export function QuerySelectionPlaceholder( props: QuerySelectionPlaceholderProps ) {
	const { blockConfig, onDisplayQueryKeySelect, onQueryInputsSelect } = props;
	const { instructions, settings, displayQueriesToSelectors } = blockConfig;

	const iconElement: IconType = ( settings.icon as IconType ) ?? cloud;

	const [ selectedDisplayQueryKey, setSelectedDisplayQueryKey ] = useState< string >( '' );
	const [ showSelectors, setShowSelectors ] = useState< boolean >( false );

	function handleSelectorOnSelect( inputs: RemoteDataQueryInput[] ) {
		setShowSelectors( false );
		onDisplayQueryKeySelect( selectedDisplayQueryKey );
		onQueryInputsSelect( inputs );
	}

	function handleDisplayQueryKeyOnSelect( displayQueryKey: string ) {
		setSelectedDisplayQueryKey( displayQueryKey );
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
					{ Object.entries( displayQueriesToSelectors ).map(
						( [ displayQueryKey, displayQueryConfig ] ) => (
							<Button
								key={ displayQueryKey }
								variant="primary"
								onClick={ () => {
									handleDisplayQueryKeyOnSelect( displayQueryKey );
								} }
							>
								{ displayQueryConfig.name }
							</Button>
						)
					) }
				</ToggleGroupControl>
			) }
			{ showSelectors && (
				<ItemSelectQueryType
					blockName={ blockConfig.name }
					selectors={ getSelectorsForDisplayQuery( blockConfig.name, selectedDisplayQueryKey ) }
					displayQueryKey={ selectedDisplayQueryKey }
					onSelect={ handleSelectorOnSelect }
				/>
			) }
		</PlaceholderComponent>
	);
}
