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
		blockConfig: { name: blockName, panels },
		onSelect,
	} = props;

	return (
		<ButtonGroup className="remote-data-blocks-button-group">
			{ panels.map( panel => {
				const title = panel.name;
				const panelProps = {
					blockName,
					onSelect,
					queryKey: panel.query_key,
					title,
				};

				switch ( panel.type ) {
					case 'search':
						return <SearchModal key={ panel.name } { ...panelProps } />;
					case 'list':
						return <ListModal key={ panel.name } { ...panelProps } />;
					case 'input':
						return <InputModal key={ panel.name } inputs={ panel.inputs } { ...panelProps } />;
				}

				return null;
			} ) }
		</ButtonGroup>
	);
}
