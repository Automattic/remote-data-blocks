import { ButtonGroup } from '@wordpress/components';

import { InputPanel } from './input-panel';
import { ListPanel } from './list-panel';
import { SearchPanel } from './search-panel';

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
				const panelProps = {
					blockName,
					onSelect,
					panel,
				};

				switch ( panel.type ) {
					case 'search':
						return <SearchPanel key={ panel.name } { ...panelProps } />;
					case 'list':
						return <ListPanel key={ panel.name } { ...panelProps } />;
					case 'input':
						return <InputPanel key={ panel.name } { ...panelProps } />;
				}

				return null;
			} ) }
		</ButtonGroup>
	);
}
