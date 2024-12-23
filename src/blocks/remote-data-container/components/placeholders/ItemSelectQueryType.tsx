import { ButtonGroup } from '@wordpress/components';

import { DataViewsModal } from '@/blocks/remote-data-container/components/modals/DataViewsModal';
import { InputModal } from '@/blocks/remote-data-container/components/modals/InputModal';

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
					case 'list':
						return <DataViewsModal key={ title } { ...selectorProps } />;
					case 'input':
						return <InputModal key={ title } inputs={ selector.inputs } { ...selectorProps } />;
				}

				return null;
			} ) }
		</ButtonGroup>
	);
}
