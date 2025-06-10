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
	onDisplayQueryKeySelect: ( displayQueryKey: string ) => void;
	onSelectorQueryKeySelect: ( selectorQueryKey: string ) => void;
	onQueryInputsSelect: ( inputs: RemoteDataQueryInput[] ) => void;
}

export function QuerySelectionPlaceholder( props: QuerySelectionPlaceholderProps ) {
	const { blockConfig, onDisplayQueryKeySelect, onSelectorQueryKeySelect, onQueryInputsSelect } =
		props;
	const { instructions, settings, displayQueriesToSelectors } = blockConfig;

	const iconElement: IconType = ( settings.icon as IconType ) ?? cloud;

	// The keys represent the display query keys.
	const displayQueryKeys: string[] = Object.keys( displayQueriesToSelectors );

	const [ selectedDisplayQueryKey, setSelectedDisplayQueryKey ] = useState< string >( '' );
	const [ showSelectors, setShowSelectors ] = useState< boolean >( false );

	function handleSelectorOnSelect( inputs: RemoteDataQueryInput[], selectorQueryKey?: string ) {
		setShowSelectors( false );
		onDisplayQueryKeySelect( selectedDisplayQueryKey );
		// ToDo: Should the selector key be set to the display query key if no selector query key is provided?
		onSelectorQueryKeySelect( selectorQueryKey ?? selectedDisplayQueryKey );
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
					{ displayQueryKeys.map( ( displayQueryKey: string ) => (
						<Button
							key={ displayQueryKey }
							variant="primary"
							onClick={ () => {
								handleDisplayQueryKeyOnSelect( displayQueryKey );
							} }
						>
							{ displayQueryKey
								.replace( /[^a-zA-Z0-9]/g, ' ' )
								.replace( /\b\w/g, ( initialLetter: string ) => initialLetter.toUpperCase() ) }
						</Button>
					) ) }
				</ToggleGroupControl>
			) }
			{ showSelectors && (
				<ItemSelectQueryType
					blockName={ blockConfig.name }
					selectors={ displayQueriesToSelectors[ selectedDisplayQueryKey ] ?? [] }
					displayQueryKey={ selectedDisplayQueryKey }
					onSelect={ handleSelectorOnSelect }
				/>
			) }
		</PlaceholderComponent>
	);
}
