import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { ItemList } from '@/blocks/remote-data-container/components/item-list/ItemList';
import { ModalWithButtonTrigger } from '@/blocks/remote-data-container/components/modals//BaseModal';
import { useModalState } from '@/blocks/remote-data-container/hooks/useModalState';
import { useRemoteData } from '@/blocks/remote-data-container/hooks/useRemoteData';
import { sendTracksEvent } from '@/blocks/remote-data-container/utils/tracks';
import { getBlockAvailableBindings, getBlockDataSourceType } from '@/utils/localized-block-data';

interface DataViewsModalProps {
	blockName: string;
	headerImage?: string;
	inputVariables: InputVariable[];
	onSelect: ( data: RemoteDataQueryInput ) => void;
	queryKey: string;
	title: string;
}

export const DataViewsModal: React.FC< DataViewsModalProps > = props => {
	const { blockName, inputVariables, onSelect, queryKey, title } = props;
	const availableBindings = getBlockAvailableBindings( blockName );

	const { close, isOpen, open } = useModalState();
	const {
		data,
		fetch,
		loading,
		page,
		searchInput,
		setPage,
		setSearchInput,
		supportsSearch,
		totalItems,
		totalPages,
	} = useRemoteData( {
		blockName,
		inputVariables,
		queryKey,
	} );

	useEffect( () => {
		void fetch( {} );
	}, [] );

	function onSelectItem( input: RemoteDataQueryInput ): void {
		onSelect( input );
		sendTracksEvent( 'remotedatablocks_add_block', {
			action: 'select_item',
			selected_option: 'search_from_list',
			data_source_type: getBlockDataSourceType( blockName ),
		} );
		close();
	}

	return (
		<ModalWithButtonTrigger
			buttonText={ __( 'Choose' ) }
			className="rdb-editor_data-views-modal"
			isOpen={ isOpen }
			onClose={ close }
			onOpen={ open }
			title={ title }
		>
			<ItemList
				availableBindings={ availableBindings }
				blockName={ props.blockName }
				loading={ loading }
				onSelect={ onSelectItem }
				page={ page }
				results={ data?.results }
				searchInput={ searchInput }
				setPage={ setPage }
				setSearchInput={ setSearchInput }
				supportsSearch={ supportsSearch }
				totalItems={ totalItems }
				totalPages={ totalPages }
			/>
		</ModalWithButtonTrigger>
	);
};
