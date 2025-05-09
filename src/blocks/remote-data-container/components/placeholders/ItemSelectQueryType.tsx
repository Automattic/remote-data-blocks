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
	onSelect: ( data: RemoteDataQueryInput[] ) => void;
}

export function ItemSelectQueryType( props: ItemSelectQueryTypeProps ) {
	const {
		blockConfig: { name: blockName, selectors },
		onSelect,
	} = props;

	const [ activeModal, setActiveModal ] = useState< {
		type: 'search' | 'list' | 'manual-input';
		selector: ( typeof selectors )[ 0 ];
	} | null >( null );

	const [ activePopover, setActivePopover ] = useState< {
		selector: ( typeof selectors )[ 0 ];
	} | null >( null );

	const handleSelectorClick = ( selector: ( typeof selectors )[ 0 ] ) => {
		switch ( selector.type ) {
			case 'search':
			case 'list':
				setActiveModal( { type: selector.type, selector } );
				break;
			case 'load-without-input':
				onSelect( [ {} ] );
				break;
			case 'manual-input':
				if ( selector.inputs.length === 1 && selector.inputs[ 0 ] ) {
					setActivePopover( { selector } );
				} else {
					setActiveModal( { type: 'manual-input', selector } );
				}
				break;
		}
	};

	if ( activeModal ) {
		return activeModal.type === 'manual-input' ? (
			<InputModal
				key={ activeModal.selector.name }
				inputs={ activeModal.selector.inputs }
				blockName={ blockName }
				headerImage={ activeModal.selector.image_url }
				onSelect={ onSelect }
				title={ activeModal.selector.name }
			/>
		) : (
			<DataViewsModal
				className="rdb-editor_dataviews-modal-item-select"
				key={ activeModal.selector.name }
				blockName={ blockName }
				headerImage={ activeModal.selector.image_url }
				onSelect={ onSelect }
				queryKey={ activeModal.selector.query_key }
				title={ activeModal.selector.name }
			/>
		);
	}

	if ( activePopover && activePopover.selector.inputs[ 0 ] ) {
		return (
			<InputPopover
				key={ activePopover.selector.name }
				input={ activePopover.selector.inputs[ 0 ] }
				blockName={ blockName }
				headerImage={ activePopover.selector.image_url }
				onSelect={ onSelect }
				title={ activePopover.selector.inputs[ 0 ].name ?? activePopover.selector.name }
			/>
		);
	}

	return (
		<ToggleGroupControl
			className="remote-data-blocks-button-group"
			label={ __( '' ) }
			__nextHasNoMarginBottom
			__next40pxDefaultSize
		>
			{ selectors.map( selector => {
				const title = selector.name;

				return (
					<Button
						key={ title }
						onClick={ () => handleSelectorClick( selector ) }
						value={ title }
						variant="primary"
					>
						{ selector.display_name ?? title }
					</Button>
				);
			} ) }
		</ToggleGroupControl>
	);
}
