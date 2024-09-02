import { ItemList } from '@/blocks/remote-data-container/components/item-list/item-list';
import { ModalWithButtonTrigger } from '@/blocks/remote-data-container/components/modals/base-modal';
import { useModalState } from '@/blocks/remote-data-container/hooks/use-modal-state';
import { __ } from '@/utils/i18n';

export interface ItemListModalProps {
	blockName: string;
	buttonText: string;
	headerActions?: JSX.Element;
	headerImage?: string;
	loading: boolean;
	onOpen?: () => void;
	onSelect: ( data: RemoteDataQueryInput ) => void;
	results?: RemoteData[ 'results' ];
	title: string;
}

export function ItemListModal( props: ItemListModalProps ) {
	const { close, isOpen, open } = useModalState( props.onOpen );

	function wrappedOnSelect( data: RemoteDataQueryInput ): void {
		props.onSelect( data );
		close();
	}

	return (
		<ModalWithButtonTrigger
			buttonText={ props.buttonText }
			headerImage={ props.headerImage }
			headerActions={ props.headerActions }
			isOpen={ isOpen }
			onClose={ close }
			onOpen={ open }
			title={ props.title }
		>
			<ItemList
				blockName={ props.blockName }
				loading={ props.loading }
				noResultsText={ __( 'No items found' ) }
				onSelect={ wrappedOnSelect }
				placeholderText={ __( 'Select an item' ) }
				results={ props.results }
			/>
		</ModalWithButtonTrigger>
	);
}
