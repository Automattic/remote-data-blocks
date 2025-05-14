import {
	Button,
	__experimentalToggleGroupControl as ToggleGroupControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState } from 'react';

import { DataViewsModal } from '@/blocks/remote-data-container/components/modals/DataViewsModal';
import { InputModal } from '@/blocks/remote-data-container/components/modals/InputModal';
import { InputPopover } from '@/blocks/remote-data-container/components/popovers/InputPopover';

interface ItemSelectQueryTypeProps {
	blockConfig: BlockConfig;
	initializeRemoteData: ( queryKey: string ) => void;
}

export function ItemSelectQueryType( props: ItemSelectQueryTypeProps ) {
	const {
		blockConfig: { name: blockName, selectors },
		initializeRemoteData,
	} = props;

	const [ activeSelector, setActiveSelector ] = useState< ( typeof selectors )[ 0 ] | null >(
		null
	);

	const handleSelectorClick = ( selector: ( typeof selectors )[ 0 ] ) => {
		setActiveSelector( selector );
		initializeRemoteData( selector.query_key );
	};

	if ( activeSelector ) {
		const selectorProps = {
			blockName,
			headerImage: activeSelector.image_url,
			inputVariables: activeSelector.inputs,
			onSelect: () => {},
			queryKey: activeSelector.query_key,
			title: activeSelector.name,
		};

		switch ( activeSelector.type ) {
			case 'search':
			case 'list':
				return (
					<DataViewsModal
						className="rdb-editor_dataviews-modal-item-select"
						key={ activeSelector.name }
						{ ...selectorProps }
					/>
				);
			case 'load-without-input':
				// onSelect( [ {} ] );
				return null;
			case 'manual-input':
				if ( activeSelector.inputs.length === 1 && activeSelector.inputs[ 0 ] ) {
					return (
						<InputPopover
							key={ activeSelector.name }
							input={ activeSelector.inputs[ 0 ] }
							{ ...selectorProps }
							title={ activeSelector.inputs[ 0 ].name ?? activeSelector.name }
						/>
					);
				}
				return (
					<InputModal
						key={ activeSelector.name }
						inputs={ activeSelector.inputs }
						{ ...selectorProps }
					/>
				);
			default:
				return null;
		}
	}

	return (
		<ToggleGroupControl
			className="remote-data-blocks-button-group"
			label={ __( '' ) }
			__nextHasNoMarginBottom
			__next40pxDefaultSize
		>
			{ selectors.map( selector => {
				return (
					<Button
						key={ selector.query_key }
						onClick={ () => handleSelectorClick( selector ) }
						value={ selector.query_key }
						variant="primary"
					>
						{ selector.display_name ?? selector.name }
					</Button>
				);
			} ) }
		</ToggleGroupControl>
	);
}
