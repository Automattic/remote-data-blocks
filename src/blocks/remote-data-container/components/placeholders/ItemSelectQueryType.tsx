import {
	Button,
	__experimentalToggleGroupControl as ToggleGroupControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { DataViewsModal } from '@/blocks/remote-data-container/components/modals/DataViewsModal';
import { InputModal } from '@/blocks/remote-data-container/components/modals/InputModal';
import { InputPopover } from '@/blocks/remote-data-container/components/popovers/InputPopover';

interface ItemSelectQueryTypeProps {
	blockName: string;
	selectors: Selector[];
	displayQueryKey: string;
	onSelect: ( data: RemoteDataQueryInput[], selectorQueryKey?: string ) => void;
}

export function ItemSelectQueryType( props: ItemSelectQueryTypeProps ) {
	const { blockName, selectors, displayQueryKey, onSelect } = props;

	return (
		<ToggleGroupControl
			className="remote-data-blocks-button-group"
			label={ __( '' ) }
			__nextHasNoMarginBottom
			__next40pxDefaultSize
		>
			{ selectors.map( selector => {
				const title = selector.name;
				const selectorProps = {
					blockName,
					headerImage: selector.image_url,
					inputVariables: selector.inputs,
					onSelect,
					selectorQueryKey: selector.query_key,
					displayQueryKey,
					title,
				};

				switch ( selector.type ) {
					case 'search':
					case 'list':
						return (
							<DataViewsModal
								className="rdb-editor_dataviews-modal-item-select"
								key={ title }
								{ ...selectorProps }
							/>
						);
					case 'load-without-input':
						return (
							<Button
								key={ title }
								onClick={ () => {
									onSelect( [ {} ], selector.query_key );
								} }
								variant="primary"
							>
								{ selector.name }
							</Button>
						);
					case 'manual-input':
						if ( selector.inputs.length === 1 && selector.inputs[ 0 ] ) {
							return (
								<InputPopover
									key={ title }
									input={ selector.inputs[ 0 ] }
									{ ...selectorProps }
									title={ selector.inputs[ 0 ].name ?? selector.name }
								/>
							);
						}
						return <InputModal key={ title } inputs={ selector.inputs } { ...selectorProps } />;
				}

				return null;
			} ) }
		</ToggleGroupControl>
	);
}
