import { IconType, Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { cloud } from '@wordpress/icons';

import { ItemSelectQueryType } from '@/blocks/remote-data-container/components/placeholders/ItemSelectQueryType';

interface PlaceholderSingleProps {
	blockConfig: BlockConfig;
	onSelect: ( data: RemoteDataQueryInput[] ) => void;
}

export function PlaceholderSingle( props: PlaceholderSingleProps ) {
	const { blockConfig, onSelect } = props;

	const iconElement: IconType = ( blockConfig.settings.icon as IconType ) ?? cloud;

	return (
		<Placeholder
			icon={ iconElement }
			label={ blockConfig.settings.title }
			instructions={ __( 'This block requires selection of one or more items for display.' ) }
		>
			<ItemSelectQueryType blockConfig={ blockConfig } onSelect={ onSelect } />
		</Placeholder>
	);
}
