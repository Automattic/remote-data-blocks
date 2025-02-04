import { DropdownMenu, MenuGroup, MenuItem } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { chevronRightSmall } from '@wordpress/icons';

import { DataViewsModal } from '../modals/DataViewsModal';
import { getBlocksConfig } from '@/utils/localized-block-data';

interface FieldShortcodeSelectNewProps {
	onSelectField: ( data: FieldSelection, fieldValue: string ) => void;
}

export function FieldShortcodeSelectNew( props: FieldShortcodeSelectNewProps ) {
	const { onSelectField } = props;
	const blockConfigs = getBlocksConfig();
	const nonLoopBlocks = Object.values( blockConfigs ).filter( ( { loop } ) => ! loop );
	const blocksByType = nonLoopBlocks.reduce<
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
			className="remote-data-blocks-select-new"
			icon={ chevronRightSmall }
			label=""
			text={ __( 'Select an item', 'remote-data-blocks' ) }
			popoverProps={ { placement: 'right-start', offset: 0 } }
		>
			{ () =>
				Object.entries( blocksByType ).map( ( [ dataSourceType, configs ] ) => (
					<MenuGroup key={ dataSourceType } label={ dataSourceType }>
						{ configs.map( blockConfig => (
							<DataViewsModal
								key={ blockConfig.name }
								blockName={ blockConfig.name }
								onSelectField={ onSelectField }
								queryKey={ blockConfig.selectors[ 0 ]?.query_key ?? '' }
								renderTrigger={ ( { onClick } ) => (
									<MenuItem onClick={ onClick }>
										{ blockConfig.settings?.title ?? blockConfig.name }
									</MenuItem>
								) }
							/>
						) ) }
					</MenuGroup>
				) )
			}
		</DropdownMenu>
	);
}
