import { SearchControl } from '@wordpress/components';

import { ItemListModal } from '@/blocks/remote-data-container/components/modals/ItemListModal';
import { useSearchResults } from '@/blocks/remote-data-container/hooks/useSearchResults';
import { sendTracksEvent } from '@/blocks/remote-data-container/utils/tracks';
import { getBlockDataSourceType } from '@/utils/localized-block-data';

interface SearchModalProps {
	blockName: string;
	headerImage?: string;
	onSelect: ( data: RemoteDataQueryInput ) => void;
	queryKey: string;
	title: string;
}

export function SearchModal( props: SearchModalProps ) {
	const { blockName, onSelect, queryKey, title } = props;

	const { loading, onChange, onKeyDown, results, searchTerms } = useSearchResults( {
		blockName,
		queryKey,
	} );

	function onSelectItem( data: RemoteDataQueryInput ): void {
		onSelect( data );
		sendTracksEvent( 'remotedatablocks_add_block', {
			action: 'select_item',
			selected_option: 'search_from_list',
			data_source_type: getBlockDataSourceType( blockName ),
		} );
	}

	return (
		<ItemListModal
			blockName={ blockName }
			buttonText="Search for an item"
			headerActions={
				<SearchControl
					value={ searchTerms }
					label="Search"
					hideLabelFromVision={ true }
					onChange={ onChange }
					onKeyDown={ onKeyDown }
				/>
			}
			headerImage={ props.headerImage }
			loading={ loading }
			onSelect={ onSelectItem }
			results={ results }
			title={ title }
		/>
	);
}
