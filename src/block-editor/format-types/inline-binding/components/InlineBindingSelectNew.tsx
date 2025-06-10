import { DropdownMenu, MenuGroup, MenuItem } from '@wordpress/components';
import { DropdownMenuProps } from '@wordpress/components/build-types/dropdown-menu/types';
import { __ } from '@wordpress/i18n';
import { chevronRightSmall } from '@wordpress/icons';

import { DataViewsModal } from '@/blocks/remote-data-container/components/modals/DataViewsModal';
import { getBlocksConfig } from '@/utils/localized-block-data';

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
						{ configs.map( blockConfig => (
							<MenuGroup
								key={ blockConfig.name }
								label={ blockConfig.settings?.title ?? blockConfig.name }
							>
								{ Object.keys( blockConfig.displayQueriesToSelectors ).map( displayQueryKey => {
									const selectors = blockConfig.displayQueriesToSelectors[ displayQueryKey ] ?? [];

									// For now, we will use the first compatible selector, but this
									// should be improved.
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
													{ displayQueryKey
														.replace( /[^a-zA-Z0-9]/g, ' ' )
														.replace( /\b\w/g, ( initialLetter: string ) =>
															initialLetter.toUpperCase()
														) }
												</MenuItem>
											) }
										/>
									);
								} ) }
							</MenuGroup>
						) ) }
					</MenuGroup>
				) )
			}
		</DropdownMenu>
	);
}
