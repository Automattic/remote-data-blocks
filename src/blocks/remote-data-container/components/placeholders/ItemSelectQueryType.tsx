import { Button } from '@wordpress/components';

import { DataViewsModal } from '@/blocks/remote-data-container/components/modals/DataViewsModal';
import { InputModal } from '@/blocks/remote-data-container/components/modals/InputModal';
import { InputPopover } from '@/blocks/remote-data-container/components/popovers/InputPopover';

// Inline type for selector from BlockConfig
// (could also import BlockConfig and use BlockConfig['selectors'][0] if preferred)
type Selector = {
	image_url?: string;
	inputs: InputVariable[];
	name: string;
	query_key: string;
	display_name?: string;
	type: string;
};

interface ItemSelectQueryTypeProps {
	blockName: string;
	selector: Selector;
	onSelect: ( data: RemoteDataQueryInput[] ) => void;
}

export function ItemSelectQueryType( props: ItemSelectQueryTypeProps ) {
	const { blockName, selector, onSelect } = props;
	const title = selector.name;
	const selectorProps = {
		blockName,
		headerImage: selector.image_url,
		inputVariables: selector.inputs,
		onSelect,
		queryKey: selector.query_key,
		title,
	};

	switch ( selector.type ) {
		case 'search':
		case 'list':
			return (
				<DataViewsModal className="rdb-editor_dataviews-modal-item-select" { ...selectorProps } />
			);
		case 'load-without-input':
			return (
				<Button onClick={ () => onSelect( [ {} ] ) } variant="primary">
					{ selector.name }
				</Button>
			);
		case 'manual-input':
			if ( selector.inputs.length === 1 && selector.inputs[ 0 ] ) {
				return (
					<InputPopover
						input={ selector.inputs[ 0 ] }
						{ ...selectorProps }
						title={ selector.inputs[ 0 ].name ?? selector.name }
					/>
				);
			}
			return <InputModal inputs={ selector.inputs } { ...selectorProps } />;
	}

	return null;
}
