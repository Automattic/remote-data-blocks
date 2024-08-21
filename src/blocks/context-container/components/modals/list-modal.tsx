import { ItemListModal } from './item-list-modal';
import { useRemoteData } from '../../hooks/use-remote-data';

interface ListModalProps {
	blockName: string;
	onSelect: ( data: RemoteDataQueryInput ) => void;
	queryKey: string;
	title: string;
}

export function ListModal( props: ListModalProps ) {
	const { blockName, onSelect, queryKey, title } = props;

	const { data, execute, loading } = useRemoteData( blockName, queryKey );

	return (
		<ItemListModal
			blockName={ blockName }
			buttonText="Select an item from a list"
			loading={ loading }
			onOpen={ () => void execute( {} ) }
			onSelect={ onSelect }
			results={ data?.results }
			title={ title }
		/>
	);
}
