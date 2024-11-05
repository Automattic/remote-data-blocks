import { ItemListModal } from '@/blocks/remote-data-container/components/modals/ItemListModal';
import { useSearchResults } from '@/blocks/remote-data-container/hooks/useSearchResults';

interface SearchModalProps {
	blockName: string;
	headerImage?: string;
	onSelect: ( data: RemoteDataQueryInput ) => void;
	queryKey: string;
	title: string;
}

export function SearchModal( props: SearchModalProps ) {
	const { blockName, onSelect, queryKey, title } = props;

	const { loading, onChange, results } = useSearchResults( {
		blockName,
		queryKey,
	} );

	return (
		<ItemListModal
			blockName={ blockName }
			buttonText="Search for an item"
			headerImage={ props.headerImage }
			loading={ loading }
			onSearch={ onChange }
			onSelect={ onSelect }
			results={ results }
			title={ title }
		/>
	);
}
