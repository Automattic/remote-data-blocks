import { ButtonGroup } from '@wordpress/components';

import { InputModal } from './modals/input-modal';
import { ListModal } from './modals/list-modal';
import { SearchModal } from './modals/search-modal';

interface ItemSelectQueryTypeProps {
	blockConfig: BlockConfig;
	onSelect: ( data: RemoteDataQueryInput ) => void;
}

export function ItemSelectQueryType( props: ItemSelectQueryTypeProps ) {
	const {
		blockConfig: { name: blockName, selectors },
		onSelect,
	} = props;

	return (
		<ButtonGroup className="remote-data-blocks-button-group">
			{ selectors.map( selector => {
				const title = selector.name;
				const selectorProps = {
					blockName,
					headerImage: selector.image_url,
					onSelect,
					queryKey: selector.query_key,
					title,
				};

				switch ( selector.type ) {
					case 'search':
						return <SearchModal key={ title } { ...selectorProps } />;
					case 'list':
						return <ListModal key={ title } { ...selectorProps } />;
					case 'input':
						return <InputModal key={ title } inputs={ selector.inputs } { ...selectorProps } />;
				}

				return null;
			} ) }
		</ButtonGroup>
	);
}
