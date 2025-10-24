import { DropdownMenu, MenuGroup, MenuItem } from '@wordpress/components';
import { DropdownMenuProps } from '@wordpress/components/build-types/dropdown-menu/types';
import { __ } from '@wordpress/i18n';
import { chevronRightSmall } from '@wordpress/icons';

import { DataViewsModal } from '@/blocks/remote-data-container/components/modals/DataViewsModal';
import {
	getBlocksConfig,
	getFirstDisplayQueryKey,
	getSelectorsForDisplayQuery,
} from '@/utils/localized-block-data';

type InlineBindingSelectNewProps = Omit< DropdownMenuProps, 'label' > & {
	onSelectField: ( data: FieldSelection, fieldValue: string ) => void;
	label?: string;
};

export function InlineBindingSelectNew( props: InlineBindingSelectNewProps ) {
	const { onSelectField, ...restProps } = props;
	const blockConfigs = getBlocksConfig();
	const blocksByType = Object.values( blockConfigs ).reduce<
		Record< string, Array< BlocksConfig[ keyof BlocksConfig ] > >
	>( ( source, blockConfig ) => {
		const type = blockConfig.dataSourceType;
		if ( ! source[ type ] ) {
			source[ type ] = [];
		}
		source[ type ].push( blockConfig );
		return source;
	}, {} );

	return (
		<DropdownMenu
			icon={ chevronRightSmall }
			label=""
			text={ __( 'Select an item', 'remote-data-blocks' ) }
			popoverProps={ {
				className: 'remote-data-blocks-inline-binding-dropdown remote-data-blocks-select-new',
				placement: 'right-start',
				offset: 0,
			} }
			{ ...restProps }
		>
			{ () =>
				Object.entries( blocksByType ).map( ( [ dataSourceType, configs ] ) => (
					<MenuGroup key={ dataSourceType } label={ dataSourceType }>
						{ configs.map( blockConfig => {
							// ToDo: We are picking the first display query, and the first compatible selector for now.
							const displayQueryKey = getFirstDisplayQueryKey( blockConfig.name );
							const selectors = getSelectorsForDisplayQuery( blockConfig.name, displayQueryKey );
							const compatibleSelector = selectors.find( selector =>
								[ 'list', 'search' ].includes( selector.type )
							);

							if ( ! compatibleSelector ) {
								return null;
							}

							return (
								<DataViewsModal
									key={ blockConfig.name }
									blockName={ blockConfig.name }
									headerImage={ compatibleSelector.image_url }
									onSelectField={ onSelectField }
									selectorQueryKey={ compatibleSelector.query_key }
									displayQueryKey={ displayQueryKey }
									renderTrigger={ ( { onClick } ) => (
										<MenuItem onClick={ onClick }>
											{ blockConfig.settings?.title ?? blockConfig.name }
										</MenuItem>
									) }
								/>
							);
						} ) }
					</MenuGroup>
				) )
			}
		</DropdownMenu>
	);
}
