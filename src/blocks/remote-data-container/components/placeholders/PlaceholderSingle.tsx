import { IconType, Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { cloud } from '@wordpress/icons';

import { ItemSelectQueryType } from '@/blocks/remote-data-container/components/placeholders/ItemSelectQueryType';

interface PlaceholderSingleProps {
	blockConfig: BlockConfig;
	onSelect: ( data: RemoteDataQueryInput ) => void;
}

export function PlaceholderSingle( props: PlaceholderSingleProps ) {
	const { blockConfig, onSelect } = props;

	const supportsBulk = blockConfig?.selectors?.some( selector => selector.supports_bulk ) ?? false;

	const iconElement: IconType = ( blockConfig.settings.icon as IconType ) ?? cloud;

	return (
		<Placeholder
			icon={ iconElement }
			label={ blockConfig.settings.title }
			instructions={
				supportsBulk
					? __( 'This block requires selection of one or more items for display.' )
					: __( 'This block requires selection of a single item for display.' )
			}
		>
			<ItemSelectQueryType blockConfig={ blockConfig } onSelect={ onSelect } />
		</Placeholder>
	);
}
