import { TabPanel } from '@wordpress/components';

import { FieldShortcodeSelectExisting } from '@/blocks/remote-data-container/components/field-shortcode/select-existing';
import { FieldShortcodeSelectMeta } from '@/blocks/remote-data-container/components/field-shortcode/select-meta';
import { FieldShortcodeSelectNew } from '@/blocks/remote-data-container/components/field-shortcode/select-new';

interface FieldShortcodeSelectTabsProps {
	onSelectField: ( data: FieldSelection, fieldValue: string ) => void;
	onSelectItem: ( config: BlockConfig, data: RemoteDataQueryInput ) => void;
}

export function FieldShortcodeSelectTabs( props: FieldShortcodeSelectTabsProps ) {
	return (
		<TabPanel
			tabs={ [
				{
					name: 'new',
					title: 'Select an item',
				},
				{
					name: 'existing',
					title: 'Existing items',
				},
				{
					name: 'meta',
					title: 'Query metadata',
				},
			] }
		>
			{ tab => {
				switch ( tab.name ) {
					case 'existing':
						return <FieldShortcodeSelectExisting onSelectField={ props.onSelectField } />;

					case 'new':
						return <FieldShortcodeSelectNew onSelectItem={ props.onSelectItem } />;

					case 'meta':
						return <FieldShortcodeSelectMeta onSelectField={ props.onSelectField } />;
				}
			} }
		</TabPanel>
	);
}
