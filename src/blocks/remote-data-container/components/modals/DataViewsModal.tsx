import { BaseControl, Button, Modal, __experimentalHStack as HStack } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { ItemList } from '@/blocks/remote-data-container/components/item-list/ItemList';
import { useModalState } from '@/blocks/remote-data-container/hooks/useModalState';
import { useRemoteData } from '@/blocks/remote-data-container/hooks/useRemoteData';
import { sendTracksEvent } from '@/blocks/remote-data-container/utils/tracks';
import {
	getBlockAvailableBindings,
	getBlockConfig,
	getBlockDataSourceType,
} from '@/utils/localized-block-data';

interface DataViewsModalProps {
	className?: string;
	blockName: string;
	headerImage?: string;
	onSelect?: ( data: RemoteDataQueryInput ) => void;
	onSelectField?: ( data: FieldSelection, fieldValue: string ) => void;
	queryKey: string;
	renderTrigger?: ( props: { onClick: () => void } ) => React.ReactNode;
	title?: string;
}

export const DataViewsModal: React.FC< DataViewsModalProps > = props => {
	const { className, blockName, onSelect, onSelectField, queryKey, renderTrigger, title } = props;

	const blockConfig = getBlockConfig( blockName );
	const availableBindings = getBlockAvailableBindings( blockName );

	// Supports bulk selection
	const supportsBulk = blockConfig?.selectors?.some( selector => selector.supports_bulk ) ?? false;
	// Selected items
	const [ selectedItems, setSelectedItems ] = useState< string[] >( [] );
	// Find the ID field from availableBindings
	const idField =
		Object.entries( availableBindings ).find(
			( [ _, binding ] ) => binding.type === 'id'
		)?.[ 0 ] ?? 'id';
	// Total selected items
	const itemCountLabel =
		selectedItems.length > 1 ? __( 'items selected in total' ) : __( 'item selected in total' );

	const { close, isOpen, open } = useModalState();
	const {
		data,
		hasNextPage,
		loading,
		page,
		searchInput,
		setPage,
		setPerPage,
		setSearchInput,
		supportsSearch,
		totalItems,
		totalPages,
	} = useRemoteData( { blockName, fetchOnMount: true, queryKey } );

	function onSelectItem( input: RemoteDataQueryInput ): void {
		onSelect?.( input );
		sendTracksEvent( 'add_block', {
			action: 'select_item',
			selected_option: 'search_from_list',
			data_source_type: getBlockDataSourceType( blockName ),
		} );
		close();
	}

	const triggerElement = renderTrigger ? (
		renderTrigger( { onClick: open } )
	) : (
		<Button variant="primary" onClick={ open }>
			{ __( 'Choose' ) }
		</Button>
	);

	return (
		<>
			{ triggerElement }
			{ isOpen && (
				<Modal
					className={ supportsBulk ? `${ className } rdb-dataviews-bulk-actions-modal` : className }
					isFullScreen
					onRequestClose={ close }
					title={ blockConfig?.settings?.title ?? title }
				>
					<ItemList
						availableBindings={ availableBindings }
						blockName={ blockName }
						hasNextPage={ hasNextPage ?? false }
						idField={ idField }
						loading={ loading }
						onSelect={ onSelect ? onSelectItem : close }
						onSelectField={ onSelectField }
						page={ page }
						remoteData={ data }
						searchInput={ searchInput }
						selectedItems={ selectedItems }
						setPage={ setPage }
						setPerPage={ setPerPage }
						setSearchInput={ setSearchInput }
						setSelectedItems={ setSelectedItems }
						supportsBulk={ supportsBulk }
						supportsSearch={ supportsSearch }
						totalItems={ totalItems }
						totalPages={ totalPages }
					/>
					{ supportsBulk && ! loading && (
						<>
							{ selectedItems.length > 1 && (
								<BaseControl
									className="rdb-dataviews-bulk-actions-footer__item-count-total"
									__nextHasNoMarginBottom
								>
									<BaseControl.VisualLabel style={ { marginBottom: '0' } }>
										{ selectedItems.length } { itemCountLabel }
									</BaseControl.VisualLabel>
								</BaseControl>
							) }

							<HStack className="rdb-dataviews-bulk-actions-footer__selection-total">
								<Button
									disabled={ selectedItems.length === 0 }
									onClick={ () => setSelectedItems( [] ) }
									variant="secondary"
								>
									{ __( 'Cancel' ) }
								</Button>
								<Button
									disabled={ selectedItems.length === 0 }
									onClick={ () => onSelectItem( { [ idField ]: selectedItems.join( ',' ) } ) }
									variant="primary"
								>
									{ __( 'Save' ) }
								</Button>
							</HStack>
						</>
					) }
				</Modal>
			) }
		</>
	);
};
