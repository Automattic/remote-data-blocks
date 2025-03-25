import { IconType, Placeholder as PlaceholderComponent } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { cloud } from '@wordpress/icons';

import { ItemSelectQueryType } from '@/blocks/remote-data-container/components/placeholders/ItemSelectQueryType';

export interface PlaceholderProps {
	blockConfig: BlockConfig;
	onSelect: ( input: RemoteDataQueryInput[] ) => void;
}

export function Placeholder( props: PlaceholderProps ) {
	const { blockConfig, onSelect } = props;
	const { instructions, settings } = blockConfig;

	const iconElement: IconType = ( settings.icon as IconType ) ?? cloud;

	return (
		<PlaceholderComponent
			icon={ iconElement }
			label={ settings.title }
			instructions={
				instructions ?? __( 'This block requires selection of one or more items for display.' )
			}
		>
			<ItemSelectQueryType blockConfig={ blockConfig } onSelect={ onSelect } />
		</PlaceholderComponent>
	);
}
