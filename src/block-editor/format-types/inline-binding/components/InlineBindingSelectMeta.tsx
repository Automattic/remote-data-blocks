import { DropdownMenu, MenuGroup } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { chevronRightSmall } from '@wordpress/icons';

import { FieldSelectionFromMetaFields } from '@/block-editor/format-types/inline-binding/components/InlineBindingSelection';
import { useExistingRemoteData } from '@/block-editor/format-types/inline-binding/hooks/useExistingRemoteData';
import { getBlocksConfig } from '@/utils/localized-block-data';

interface InlineBindingSelectMetaProps {
	onSelectField: ( data: FieldSelection, fieldValue: string ) => void;
}

export function InlineBindingSelectMeta( props: InlineBindingSelectMetaProps ) {
	const blockConfigs = getBlocksConfig();
	const remoteDatas: RemoteData[] = useExistingRemoteData();

	return remoteDatas.length > 0 ? (
		<DropdownMenu
			icon={ chevronRightSmall }
			label=""
			text={ __( 'Query metadata', 'remote-data-blocks' ) }
			popoverProps={ {
				className: 'remote-data-blocks-inline-binding-dropdown remote-data-blocks-select-meta',
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
						<FieldSelectionFromMetaFields
							onSelectField={ ( data, fieldValue ) =>
								props.onSelectField( { ...data, selectionPath: 'select_meta_tab' }, fieldValue )
							}
							remoteData={ remoteData }
						/>
					</MenuGroup>
				) )
			}
		</DropdownMenu>
	) : undefined;
}
