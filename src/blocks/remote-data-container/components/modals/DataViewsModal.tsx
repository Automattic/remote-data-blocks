import { Button, Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { useModalState } from '../../hooks/useModalState';
import { ItemList } from '../item-list/ItemList';
import { useSearchResults } from '@/blocks/remote-data-container/hooks/useSearchResults';
import { getBlockAvailableBindings, getBlockConfig } from '@/utils/localized-block-data';

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

	const {
		loading,
		data: remoteData,
		searchTerms,
		setSearchTerms,
	} = useSearchResults( {
		blockName,
		queryKey,
	} );

	const { close, isOpen, open } = useModalState();

	const handleSelect = ( data: RemoteDataQueryInput ): void => {
		onSelect?.( data );
	};

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
					className={ className }
					isFullScreen
					onRequestClose={ close }
					title={ title ?? blockConfig?.settings?.title }
				>
					<ItemList
						availableBindings={ availableBindings }
						blockName={ blockName }
						loading={ loading }
						onSelect={ handleSelect }
						onSelectField={ onSelectField }
						remoteData={ remoteData ?? undefined }
						searchTerms={ searchTerms }
						setSearchTerms={ setSearchTerms }
					/>
				</Modal>
			) }
		</>
	);
};
