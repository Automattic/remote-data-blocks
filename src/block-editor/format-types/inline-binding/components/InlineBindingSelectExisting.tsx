import { DropdownMenu, MenuGroup } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { chevronRightSmall } from '@wordpress/icons';

import { FieldSelectionFromAvailableBindings } from '@/block-editor/format-types/inline-binding/components/InlineBindingSelection';
import { getBlocksConfig } from '@/utils/localized-block-data';

interface InlineBindingSelectExistingProps {
	onSelectField: ( data: FieldSelection, fieldValue: string ) => void;
	remoteData: RemoteData[];
}

export function InlineBindingSelectExisting( props: InlineBindingSelectExistingProps ) {
	const blockConfigs = getBlocksConfig();
	const { remoteData: remoteDatas } = props;

	return remoteDatas.length > 0 ? (
		<DropdownMenu
			icon={ chevronRightSmall }
			label=""
			text={ __( 'Existing items', 'remote-data-blocks' ) }
			popoverProps={ {
				className: 'remote-data-blocks-inline-binding-dropdown remote-data-blocks-select-existing',
				placement: 'right-start',
				offset: 0,
			} }
		>
			{ () =>
				remoteDatas.map( remoteData => (
					<MenuGroup
						key={ remoteData.blockName }
						label={ blockConfigs[ remoteData.blockName ]?.settings.title ?? remoteData.blockName }
					>
						<FieldSelectionFromAvailableBindings
							onSelectField={ ( data, fieldValue ) =>
								props.onSelectField( { ...data, selectionPath: 'select_existing_tab' }, fieldValue )
							}
							remoteData={ remoteData }
						/>
					</MenuGroup>
				) )
			}
		</DropdownMenu>
	) : undefined;
}
