import { SearchControl } from '@wordpress/components';

import { ItemListModal } from './item-list-modal';
import { useSearchResults } from '../../hooks/use-search-results';

interface SearchModalProps {
	blockName: string;
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

	return (
		<ItemListModal
			blockName={ blockName }
			buttonText="Search for an item"
			loading={ loading }
			onSelect={ onSelect }
			results={ results }
			searchControl={
				<SearchControl
					value={ searchTerms }
					label="Search"
					hideLabelFromVision={ true }
					onChange={ onChange }
					onKeyDown={ onKeyDown }
				/>
			}
			title={ title }
		/>
	);
}
