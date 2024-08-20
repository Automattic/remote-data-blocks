import { __experimentalHeading as Heading } from '@wordpress/components';

import { ItemSelectQueryType } from './item-select-query-type';
import { getBlocksConfig } from '../../../utils/localized-block-data';

interface FieldShortcodeSelectNewProps {
	onSelectItem: ( config: BlockConfig, data: RemoteDataQueryInput ) => void;
}

export function FieldShortcodeSelectNew( props: FieldShortcodeSelectNewProps ) {
	return (
		<div className="remote-data-blocks-select-new">
			{ Object.values( getBlocksConfig() )
				.filter( ( { loop } ) => ! loop )
				.map( blockConfig => (
					<div className="remote-data-blocks-new-item" key={ blockConfig.name }>
						<Heading className="remote-data-blocks-new-item-heading" level="4">
							{ blockConfig.title }
						</Heading>
						<ItemSelectQueryType
							blockConfig={ blockConfig }
							onSelect={ ( data: RemoteDataQueryInput ) => props.onSelectItem( blockConfig, data ) }
						/>
					</div>
				) ) }
		</div>
	);
}
